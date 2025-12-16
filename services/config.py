"""
Configuration loader for Face Recognition Service.

Loads environment variables from .env file.
"""

import os
from pathlib import Path
from typing import Optional

from dotenv import load_dotenv

# Load .env file from project root
ENV_PATH = Path(__file__).resolve().parent.parent / ".env"
load_dotenv(ENV_PATH)


class Settings:
    """Application settings loaded from environment variables."""
    
    # Gateway Database
    # DB_HOST can be "host:server_port" format (e.g., "172.16.3.17:1991")
    # where server_port is the SSH tunnel/server port, and DB_PORT is MySQL port
    _raw_db_host: str = os.getenv("DB_HOST", "127.0.0.1")
    
    # Parse host and server port from DB_HOST
    if ":" in _raw_db_host:
        _host_parts = _raw_db_host.split(":", 1)
        DB_HOST: str = _host_parts[0]
        DB_SERVER_PORT: int = int(_host_parts[1])
    else:
        DB_HOST: str = _raw_db_host
        DB_SERVER_PORT: int = 0  # Not using server port
    
    # MySQL port (default 3306)
    DB_PORT: int = int(os.getenv("DB_PORT", "3306"))
    DB_DATABASE: str = os.getenv("DB_DATABASE", "sekolah_gateway")
    DB_USERNAME: str = os.getenv("DB_USERNAME", "root")
    DB_PASSWORD: str = os.getenv("DB_PASSWORD", "")
    
    # Redis Cache
    REDIS_HOST: str = os.getenv("REDIS_HOST", "127.0.0.1")
    REDIS_PORT: int = int(os.getenv("REDIS_PORT", "6379"))
    REDIS_DB: int = int(os.getenv("REDIS_DB", "0"))
    REDIS_PASSWORD: Optional[str] = os.getenv("REDIS_PASSWORD") or None
    
    # Cache TTL (seconds)
    TENANT_CACHE_TTL: int = int(os.getenv("TENANT_CACHE_TTL", "300"))  # 5 minutes
    ENCODING_CACHE_TTL: int = int(os.getenv("ENCODING_CACHE_TTL", "60"))  # 1 minute


settings = Settings()
