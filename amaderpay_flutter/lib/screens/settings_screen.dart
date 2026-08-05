import 'package:flutter/material.dart';
import 'package:hugeicons/hugeicons.dart';
import '../services/api_service.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});
  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final _api = ApiService();
  final _urlCtrl = TextEditingController();
  final _keyCtrl = TextEditingController();
  bool _saving = false;
  String? _pingResult;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    _urlCtrl.text = await _api.getServerUrl();
    _keyCtrl.text = await _api.getApiKey();
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    await _api.saveSettings(serverUrl: _urlCtrl.text, apiKey: _keyCtrl.text);
    setState(() => _saving = false);
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('✅ Settings সেভ হয়েছে'),
            backgroundColor: Colors.green),
      );
    }
  }

  Future<void> _ping() async {
    setState(() => _pingResult = 'পরীক্ষা করা হচ্ছে…');
    final ok = await _api.pingServer();
    setState(() =>
        _pingResult = ok ? '✅ Server Online' : '❌ Server Offline বা URL ভুল');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0D0D1A),
      appBar: AppBar(
        backgroundColor: const Color(0xFF0D0D1A),
        title: const Text('Settings', style: TextStyle(color: Colors.white)),
        iconTheme: const IconThemeData(color: Colors.white),
        leading: IconButton(
          icon: const HugeIcon(
            icon: HugeIcons.strokeRoundedArrowLeft01,
            color: Colors.white,
            size: 24.0,
          ),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const _SectionTitle('Server Configuration'),
          const SizedBox(height: 12),
          _DarkTextField(
            controller: _urlCtrl,
            label: 'Server URL',
            hint: 'http://192.168.1.100:3000',
            icon: HugeIcons.strokeRoundedServerStack01,
          ),
          const SizedBox(height: 12),
          _DarkTextField(
            controller: _keyCtrl,
            label: 'API Key',
            hint: 'your-secret-api-key',
            icon: HugeIcons.strokeRoundedKey01,
            obscure: true,
          ),
          const SizedBox(height: 12),
          if (_pingResult != null)
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFF13131F),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(_pingResult!,
                  style: const TextStyle(color: Colors.white70)),
            ),
          const SizedBox(height: 16),
          Row(children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: _ping,
                icon: const HugeIcon(
                  icon: HugeIcons.strokeRoundedWifi01,
                  color: Colors.white70,
                  size: 18.0,
                ),
                label: const Text('Ping Test'),
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.white70,
                  side: const BorderSide(color: Colors.white24),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: ElevatedButton.icon(
                onPressed: _saving ? null : _save,
                icon: _saving
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2))
                    : const HugeIcon(
                        icon: HugeIcons.strokeRoundedFloppyDisk,
                        color: Colors.white,
                        size: 18.0,
                      ),
                label: Text(_saving ? 'সেভ হচ্ছে…' : 'সেভ করুন'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF7B2FBE),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                ),
              ),
            ),
          ]),
          const SizedBox(height: 32),
          const _SectionTitle('About'),
          const SizedBox(height: 12),
          const _InfoTile(
            k: 'App Version',
            v: '2.0.0',
            icon: HugeIcons.strokeRoundedInformationCircle,
          ),
          const _InfoTile(
            k: 'Supported Gateways',
            v: 'bKash, Nagad, Rocket',
            icon: HugeIcons.strokeRoundedCreditCard,
          ),
          const _InfoTile(
            k: 'Min Android',
            v: '7.0 (API 24)',
            icon: HugeIcons.strokeRoundedAndroid,
          ),
          const _InfoTile(
            k: 'Target Android',
            v: '16 (API 36)',
            icon: HugeIcons.strokeRoundedSmartPhone01,
          ),
        ],
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  final String title;
  const _SectionTitle(this.title);
  @override
  Widget build(BuildContext context) => Text(
        title,
        style: const TextStyle(
            color: Colors.white54,
            fontSize: 12,
            fontWeight: FontWeight.w600,
            letterSpacing: 1.2),
      );
}

class _DarkTextField extends StatelessWidget {
  final TextEditingController controller;
  final String label, hint;
  final List<List<dynamic>> icon;
  final bool obscure;
  const _DarkTextField(
      {required this.controller,
      required this.label,
      required this.hint,
      required this.icon,
      this.obscure = false});
  @override
  Widget build(BuildContext context) => TextField(
        controller: controller,
        obscureText: obscure,
        style: const TextStyle(color: Colors.white),
        decoration: InputDecoration(
          labelText: label,
          hintText: hint,
          prefixIcon: Padding(
            padding: const EdgeInsets.all(12),
            child: HugeIcon(icon: icon, color: Colors.white54, size: 20.0),
          ),
          labelStyle: const TextStyle(color: Colors.white54),
          hintStyle: const TextStyle(color: Colors.white24),
          filled: true,
          fillColor: const Color(0xFF13131F),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10),
            borderSide: const BorderSide(color: Colors.white12),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10),
            borderSide: const BorderSide(color: Colors.white12),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(10),
            borderSide: const BorderSide(color: Color(0xFF7B2FBE)),
          ),
        ),
      );
}

class _InfoTile extends StatelessWidget {
  final String k, v;
  final List<List<dynamic>> icon;
  const _InfoTile({required this.k, required this.v, required this.icon});
  @override
  Widget build(BuildContext context) => Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: const Color(0xFF13131F),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Row(children: [
            HugeIcon(icon: icon, color: Colors.white38, size: 16.0),
            const SizedBox(width: 8),
            Text(k,
                style: const TextStyle(color: Colors.white54, fontSize: 13)),
          ]),
          Text(v,
              style: const TextStyle(
                  color: Colors.white,
                  fontSize: 13,
                  fontWeight: FontWeight.w500)),
        ]),
      );
}
