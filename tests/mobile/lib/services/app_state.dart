import 'package:flutter/material.dart';

class AppState extends ChangeNotifier {
  // API Configuration
  String _apiBaseUrl = 'http://10.0.2.2:8000'; // For Android emulator
  double _threshold = 0.35;
  
  // Camera state
  bool _isCameraReady = false;
  bool _isDetecting = false;
  
  // Detection result
  Map<String, dynamic>? _lastResult;
  List<Map<String, dynamic>> _enrollments = [];
  
  // Getters
  String get apiBaseUrl => _apiBaseUrl;
  double get threshold => _threshold;
  bool get isCameraReady => _isCameraReady;
  bool get isDetecting => _isDetecting;
  Map<String, dynamic>? get lastResult => _lastResult;
  List<Map<String, dynamic>> get enrollments => _enrollments;
  
  // API Endpoints
  String get identifyUrl => '$_apiBaseUrl/identify';
  String get enrollUrl => '$_apiBaseUrl/enroll';
  String get enrollmentsUrl => '$_apiBaseUrl/enrollments';
  
  // Setters
  void setApiBaseUrl(String url) {
    _apiBaseUrl = url.replaceAll(RegExp(r'/$'), '');
    notifyListeners();
  }
  
  void setThreshold(double value) {
    _threshold = value;
    notifyListeners();
  }
  
  void setCameraReady(bool ready) {
    _isCameraReady = ready;
    notifyListeners();
  }
  
  void setDetecting(bool detecting) {
    _isDetecting = detecting;
    notifyListeners();
  }
  
  void setLastResult(Map<String, dynamic>? result) {
    _lastResult = result;
    notifyListeners();
  }
  
  void setEnrollments(List<Map<String, dynamic>> list) {
    _enrollments = list;
    notifyListeners();
  }
}
