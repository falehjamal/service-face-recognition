"""
Multi-Tenant Database Manager with Redis Caching.

Handles dynamic database connections for multi-tenant architecture.
"""

import json
from contextlib import asynccontextmanager
from dataclasses import dataclass
from typing import Any, Dict, List, Optional

import aiomysql
import redis.asyncio as redis

from services.config import settings


@dataclass
class TenantConfig:
    """Tenant database configuration."""
    id: int
    name: str
    db_host: str
    db_port: int
    db_name: str
    db_user: str
    db_pass: Optional[str]
    status: str


class TenantManager:
    """
    Manages multi-tenant database connections with Redis caching.
    
    Features:
    - Gateway DB connection for tenant lookup
    - Dynamic tenant DB connections
    - Redis caching for tenant configs and face encodings
    """
    
    _instance: Optional["TenantManager"] = None
    _gateway_pool: Optional[aiomysql.Pool] = None
    _tenant_pools: Dict[int, aiomysql.Pool] = {}
    _redis: Optional[redis.Redis] = None
    
    def __new__(cls) -> "TenantManager":
        if cls._instance is None:
            cls._instance = super().__new__(cls)
        return cls._instance
    
    async def initialize(self) -> None:
        """Initialize gateway connection pool and Redis."""
        if self._gateway_pool is None:
            self._gateway_pool = await aiomysql.create_pool(
                host=settings.DB_HOST,
                port=settings.DB_PORT,
                user=settings.DB_USERNAME,
                password=settings.DB_PASSWORD,
                db=settings.DB_DATABASE,
                autocommit=True,
                minsize=1,
                maxsize=10,
            )
        
        if self._redis is None:
            self._redis = redis.Redis(
                host=settings.REDIS_HOST,
                port=settings.REDIS_PORT,
                db=settings.REDIS_DB,
                password=settings.REDIS_PASSWORD,
                decode_responses=True,
            )
    
    async def close(self) -> None:
        """Close all connections."""
        if self._gateway_pool:
            self._gateway_pool.close()
            await self._gateway_pool.wait_closed()
            self._gateway_pool = None
        
        for pool in self._tenant_pools.values():
            pool.close()
            await pool.wait_closed()
        self._tenant_pools.clear()
        
        if self._redis:
            await self._redis.close()
            self._redis = None
    
    async def get_tenant_config(self, tenant_id: int) -> Optional[TenantConfig]:
        """
        Get tenant configuration by ID.
        
        First checks Redis cache, then falls back to gateway database.
        """
        await self.initialize()
        
        cache_key = f"tenant:config:{tenant_id}"
        
        # Check Redis cache
        cached = await self._redis.get(cache_key)
        if cached:
            data = json.loads(cached)
            return TenantConfig(**data)
        
        # Query gateway database
        # Note: tenants table uses 'port' not 'db_port'
        # Status can be 1/'active' or 0/'inactive'
        async with self._gateway_pool.acquire() as conn:
            async with conn.cursor(aiomysql.DictCursor) as cursor:
                await cursor.execute(
                    "SELECT id, name, db_host, port, db_name, db_user, db_pass, status "
                    "FROM tenants WHERE id = %s AND (status = 'active' OR status = 1)",
                    (tenant_id,)
                )
                row = await cursor.fetchone()
        
        if not row:
            return None
        
        config = TenantConfig(
            id=row["id"],
            name=row["name"],
            db_host=row["db_host"],
            db_port=row["port"],  # tenants table uses 'port' column
            db_name=row["db_name"],
            db_user=row["db_user"],
            db_pass=row["db_pass"],
            status=row["status"],
        )
        
        # Cache in Redis
        await self._redis.setex(
            cache_key,
            settings.TENANT_CACHE_TTL,
            json.dumps(config.__dict__),
        )
        
        return config
    
    async def get_tenant_pool(self, tenant_id: int) -> Optional[aiomysql.Pool]:
        """Get or create connection pool for a tenant database."""
        if tenant_id in self._tenant_pools:
            return self._tenant_pools[tenant_id]
        
        config = await self.get_tenant_config(tenant_id)
        if not config:
            return None
        
        pool = await aiomysql.create_pool(
            host=config.db_host,
            port=config.db_port,
            user=config.db_user,
            password=config.db_pass or "",
            db=config.db_name,
            autocommit=True,
            minsize=1,
            maxsize=5,
        )
        
        self._tenant_pools[tenant_id] = pool
        return pool
    
    @asynccontextmanager
    async def get_tenant_connection(self, tenant_id: int):
        """Context manager for tenant database connection."""
        pool = await self.get_tenant_pool(tenant_id)
        if not pool:
            raise ValueError(f"Tenant {tenant_id} not found or inactive")
        
        async with pool.acquire() as conn:
            yield conn
    
    # =========================================
    # Enrollment-specific methods with caching
    # =========================================
    
    def _enrollment_table(self, tenant_id: int) -> str:
        """Get enrollment table name for tenant."""
        return f"enrollment_{tenant_id}"
    
    def _user_table(self, tenant_id: int) -> str:
        """Get user table name for tenant."""
        return f"user_{tenant_id}"
    
    async def get_enrollments(self, tenant_id: int) -> List[Dict[str, Any]]:
        """
        Get all active enrollments for a tenant.
        
        Uses Redis caching for performance.
        """
        await self.initialize()
        
        cache_key = f"tenant:{tenant_id}:enrollments"
        
        # Check cache
        cached = await self._redis.get(cache_key)
        if cached:
            return json.loads(cached)
        
        # Query tenant database
        table = self._enrollment_table(tenant_id)
        async with self.get_tenant_connection(tenant_id) as conn:
            async with conn.cursor(aiomysql.DictCursor) as cursor:
                await cursor.execute(
                    f"SELECT id, user_id, label, face_encoding, status, created_at "
                    f"FROM `{table}` WHERE status = 'active'"
                )
                rows = await cursor.fetchall()
        
        enrollments = []
        for row in rows:
            enrollments.append({
                "id": row["id"],
                "user_id": row["user_id"],
                "label": row["label"],
                "encoding": json.loads(row["face_encoding"]) if isinstance(row["face_encoding"], str) else row["face_encoding"],
                "status": row["status"],
                "created_at": str(row["created_at"]) if row["created_at"] else None,
            })
        
        # Cache results
        await self._redis.setex(
            cache_key,
            settings.ENCODING_CACHE_TTL,
            json.dumps(enrollments),
        )
        
        return enrollments
    
    async def get_user_enrollment(self, tenant_id: int, user_id: int) -> Optional[Dict[str, Any]]:
        """
        Get enrollment for a specific user.
        
        Used for verification flow where we compare against a specific user.
        """
        await self.initialize()
        
        # Check cache first
        cache_key = f"tenant:{tenant_id}:user:{user_id}:enrollment"
        cached = await self._redis.get(cache_key)
        if cached:
            return json.loads(cached)
        
        # Query tenant database
        table = self._enrollment_table(tenant_id)
        async with self.get_tenant_connection(tenant_id) as conn:
            async with conn.cursor(aiomysql.DictCursor) as cursor:
                await cursor.execute(
                    f"SELECT id, user_id, label, face_encoding, status, created_at "
                    f"FROM `{table}` WHERE user_id = %s AND status = 'active' "
                    f"ORDER BY created_at DESC LIMIT 1",
                    (user_id,)
                )
                row = await cursor.fetchone()
        
        if not row:
            return None
        
        enrollment = {
            "id": row["id"],
            "user_id": row["user_id"],
            "label": row["label"],
            "encoding": json.loads(row["face_encoding"]) if isinstance(row["face_encoding"], str) else row["face_encoding"],
            "status": row["status"],
            "created_at": str(row["created_at"]) if row["created_at"] else None,
        }
        
        # Cache for 60 seconds
        await self._redis.setex(
            cache_key,
            settings.ENCODING_CACHE_TTL,
            json.dumps(enrollment),
        )
        
        return enrollment
    
    async def add_enrollment(
        self,
        tenant_id: int,
        user_id: int,
        label: str,
        face_encoding: List[float],
    ) -> Dict[str, Any]:
        """
        Add or update enrollment for a tenant.
        
        If user_id already has an enrollment, it will be replaced (upsert behavior).
        Each user_id can only have one active enrollment.
        """
        await self.initialize()
        
        table = self._enrollment_table(tenant_id)
        encoding_json = json.dumps(face_encoding)
        
        async with self.get_tenant_connection(tenant_id) as conn:
            async with conn.cursor() as cursor:
                # Delete existing enrollment for this user_id first (upsert behavior)
                await cursor.execute(
                    f"DELETE FROM `{table}` WHERE user_id = %s",
                    (user_id,),
                )
                
                # Insert new enrollment
                await cursor.execute(
                    f"INSERT INTO `{table}` (user_id, label, face_encoding, status) "
                    f"VALUES (%s, %s, %s, 'active')",
                    (user_id, label, encoding_json),
                )
                enrollment_id = cursor.lastrowid
        
        # Invalidate cache
        await self._redis.delete(f"tenant:{tenant_id}:enrollments")
        # Also invalidate user-specific cache
        await self._redis.delete(f"tenant:{tenant_id}:user:{user_id}:enrollment")
        
        return {
            "id": enrollment_id,
            "user_id": user_id,
            "label": label,
            "tenant_id": tenant_id,
        }
    
    async def delete_enrollment(self, tenant_id: int, enrollment_id: int) -> bool:
        """Delete an enrollment by ID."""
        await self.initialize()
        
        table = self._enrollment_table(tenant_id)
        
        async with self.get_tenant_connection(tenant_id) as conn:
            async with conn.cursor() as cursor:
                await cursor.execute(
                    f"DELETE FROM `{table}` WHERE id = %s",
                    (enrollment_id,),
                )
                affected = cursor.rowcount
        
        # Invalidate cache
        await self._redis.delete(f"tenant:{tenant_id}:enrollments")
        
        return affected > 0
    
    async def delete_enrollment_by_label(self, tenant_id: int, label: str) -> bool:
        """Delete an enrollment by label."""
        await self.initialize()
        
        table = self._enrollment_table(tenant_id)
        
        async with self.get_tenant_connection(tenant_id) as conn:
            async with conn.cursor() as cursor:
                await cursor.execute(
                    f"DELETE FROM `{table}` WHERE label = %s",
                    (label,),
                )
                affected = cursor.rowcount
        
        # Invalidate cache
        await self._redis.delete(f"tenant:{tenant_id}:enrollments")
        
        return affected > 0
    
    # =========================================
    # Cache Management Methods
    # =========================================
    
    async def invalidate_enrollment_cache(self, tenant_id: int) -> bool:
        """Invalidate enrollment cache for a specific tenant."""
        await self.initialize()
        result = await self._redis.delete(f"tenant:{tenant_id}:enrollments")
        return result > 0
    
    async def invalidate_tenant_config_cache(self, tenant_id: int) -> bool:
        """Invalidate tenant config cache."""
        await self.initialize()
        result = await self._redis.delete(f"tenant:config:{tenant_id}")
        # Also remove from connection pool to force reconnect
        if tenant_id in self._tenant_pools:
            pool = self._tenant_pools.pop(tenant_id)
            pool.close()
            await pool.wait_closed()
        return result > 0
    
    async def invalidate_all_tenant_cache(self, tenant_id: int) -> Dict[str, bool]:
        """Invalidate all caches for a specific tenant."""
        await self.initialize()
        enrollment_result = await self._redis.delete(f"tenant:{tenant_id}:enrollments")
        config_result = await self._redis.delete(f"tenant:config:{tenant_id}")
        # Remove from connection pool
        if tenant_id in self._tenant_pools:
            pool = self._tenant_pools.pop(tenant_id)
            pool.close()
            await pool.wait_closed()
        return {
            "enrollment_cache_cleared": enrollment_result > 0,
            "config_cache_cleared": config_result > 0,
            "tenant_id": tenant_id,
        }
    
    async def get_cache_status(self, tenant_id: int) -> Dict[str, Any]:
        """Get cache status for a tenant."""
        await self.initialize()
        enrollment_cache = await self._redis.get(f"tenant:{tenant_id}:enrollments")
        config_cache = await self._redis.get(f"tenant:config:{tenant_id}")
        enrollment_ttl = await self._redis.ttl(f"tenant:{tenant_id}:enrollments")
        config_ttl = await self._redis.ttl(f"tenant:config:{tenant_id}")
        return {
            "tenant_id": tenant_id,
            "enrollment_cache_exists": enrollment_cache is not None,
            "enrollment_cache_ttl": enrollment_ttl if enrollment_ttl > 0 else None,
            "config_cache_exists": config_cache is not None,
            "config_cache_ttl": config_ttl if config_ttl > 0 else None,
            "connection_pool_active": tenant_id in self._tenant_pools,
        }


# Singleton instance
tenant_manager = TenantManager()
