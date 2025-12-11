import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:camera/camera.dart';

import 'theme/app_theme.dart';
import 'screens/home_screen.dart';
import 'screens/enrollment_screen.dart';
import 'services/app_state.dart';

late List<CameraDescription> cameras;

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  try {
    cameras = await availableCameras();
  } catch (e) {
    cameras = [];
  }
  
  runApp(
    ChangeNotifierProvider(
      create: (_) => AppState(),
      child: const FaceRecognitionApp(),
    ),
  );
}

class FaceRecognitionApp extends StatelessWidget {
  const FaceRecognitionApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Face Recognition',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.darkTheme,
      initialRoute: '/',
      routes: {
        '/': (context) => const HomeScreen(),
        '/enrollment': (context) => const EnrollmentScreen(),
      },
    );
  }
}
