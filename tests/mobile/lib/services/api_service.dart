import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';
import 'package:http/http.dart' as http;

class ApiService {
  static Future<Map<String, dynamic>> identify({
    required String apiUrl,
    required Uint8List imageBytes,
    required double threshold,
  }) async {
    try {
      final request = http.MultipartRequest('POST', Uri.parse(apiUrl));
      request.files.add(http.MultipartFile.fromBytes(
        'file',
        imageBytes,
        filename: 'capture.jpg',
      ));
      request.fields['threshold'] = threshold.toString();
      
      final streamedResponse = await request.send().timeout(
        const Duration(seconds: 10),
      );
      final response = await http.Response.fromStream(streamedResponse);
      
      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return {
          'error': true,
          'status': response.statusCode,
          'message': response.body,
        };
      }
    } catch (e) {
      return {
        'error': true,
        'message': e.toString(),
      };
    }
  }
  
  static Future<List<Map<String, dynamic>>> getEnrollments(String apiUrl) async {
    try {
      final response = await http.get(Uri.parse(apiUrl)).timeout(
        const Duration(seconds: 10),
      );
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        final list = data['enrollments'] as List<dynamic>? ?? [];
        return list.map((e) => Map<String, dynamic>.from(e)).toList();
      }
      return [];
    } catch (e) {
      return [];
    }
  }
  
  static Future<Map<String, dynamic>> enroll({
    required String apiUrl,
    required String name,
    required File imageFile,
  }) async {
    try {
      final request = http.MultipartRequest('POST', Uri.parse(apiUrl));
      request.fields['name'] = name;
      request.files.add(await http.MultipartFile.fromPath('file', imageFile.path));
      
      final streamedResponse = await request.send().timeout(
        const Duration(seconds: 15),
      );
      final response = await http.Response.fromStream(streamedResponse);
      
      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return {
          'error': true,
          'status': response.statusCode,
          'message': response.body,
        };
      }
    } catch (e) {
      return {
        'error': true,
        'message': e.toString(),
      };
    }
  }
}
