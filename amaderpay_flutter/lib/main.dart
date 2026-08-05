import 'package:flutter/material.dart';
import 'package:permission_handler/permission_handler.dart';
import 'screens/home_screen.dart';
import 'services/background_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initBackgroundService();
  runApp(const AmaderPayApp());
}

class AmaderPayApp extends StatelessWidget {
  const AmaderPayApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'AmaderPay SMS Gateway',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF7B2FBE),
          brightness: Brightness.dark,
        ),
        useMaterial3: true,
        fontFamily: 'Roboto',
      ),
      home: const PermissionGatePage(),
    );
  }
}

/// Handles runtime permission requests before showing the main UI
class PermissionGatePage extends StatefulWidget {
  const PermissionGatePage({super.key});
  @override
  State<PermissionGatePage> createState() => _PermissionGatePageState();
}

class _PermissionGatePageState extends State<PermissionGatePage> {
  bool _checking = true;

  @override
  void initState() {
    super.initState();
    _checkPermissions();
  }

  Future<void> _checkPermissions() async {
    // Request primary permissions first
    final smsStatus = await Permission.sms.request();
    final phoneStatus = await Permission.phone.request();
    final notifStatus = await Permission.notification.request();

    // Request battery optimization ignore (optional for background longevity)
    try {
      await Permission.ignoreBatteryOptimizations.request();
    } catch (_) {}

    final smsGranted = smsStatus.isGranted || smsStatus.isLimited;

    if (!mounted) return;
    if (smsGranted) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const HomeScreen()),
      );
    } else {
      setState(() => _checking = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: _checking
            ? const Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  CircularProgressIndicator(),
                  SizedBox(height: 16),
                  Text('Permission চেক করা হচ্ছে…'),
                ],
              )
            : Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.warning_amber_rounded, size: 64, color: Colors.orange),
                  const SizedBox(height: 16),
                  const Text('SMS ও Notification Permission দরকার', textAlign: TextAlign.center),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: _checkPermissions,
                    child: const Text('আবার চেষ্টা করুন'),
                  ),
                  TextButton(
                    onPressed: () => openAppSettings(),
                    child: const Text('Settings খুলুন'),
                  ),
                ],
              ),
      ),
    );
  }
}
