import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_background_service/flutter_background_service.dart';
import 'package:hugeicons/hugeicons.dart';
import '../models/transaction.dart';
import '../services/api_service.dart';
import 'settings_screen.dart';
import 'transactions_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final _api = ApiService();
  bool _serviceRunning = false;
  bool _serverOnline = false;
  final List<Transaction> _recent = [];
  StreamSubscription? _sub;
  int _selectedIndex = 0;

  @override
  void initState() {
    super.initState();
    _checkStatus();
    _listenToService();
  }

  Future<void> _checkStatus() async {
    final svc = FlutterBackgroundService();
    final running = await svc.isRunning();
    final ping = await _api.pingServer();
    setState(() {
      _serviceRunning = running;
      _serverOnline = ping;
    });
  }

  void _listenToService() {
    final svc = FlutterBackgroundService();
    _sub = svc.on('transaction_received').listen((data) {
      if (data == null) return;
      final txn = Transaction.fromJson(Map<String, dynamic>.from(data));
      setState(() => _recent.insert(0, txn));
    });
  }

  @override
  void dispose() {
    _sub?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0D0D1A),
      appBar: AppBar(
        backgroundColor: const Color(0xFF0D0D1A),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF7B2FBE), Color(0xFF4776E6)],
                ),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const HugeIcon(
                icon: HugeIcons.strokeRoundedMessage01,
                color: Colors.white,
                size: 20.0,
              ),
            ),
            const SizedBox(width: 10),
            const Text('NexoraPay Gateway',
                style: TextStyle(
                    color: Colors.white, fontWeight: FontWeight.bold)),
          ],
        ),
        actions: [
          IconButton(
            icon: const HugeIcon(
              icon: HugeIcons.strokeRoundedSettings01,
              color: Colors.white,
              size: 24.0,
            ),
            onPressed: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (_) => const SettingsScreen()),
            ),
          ),
        ],
      ),
      body: [
        _buildDashboard(),
        const TransactionsScreen(),
      ][_selectedIndex],
      bottomNavigationBar: NavigationBar(
        backgroundColor: const Color(0xFF13131F),
        selectedIndex: _selectedIndex,
        onDestinationSelected: (i) => setState(() => _selectedIndex = i),
        destinations: const [
          NavigationDestination(
            icon: HugeIcon(
              icon: HugeIcons.strokeRoundedDashboardSquare01,
              color: Colors.white54,
              size: 22.0,
            ),
            selectedIcon: HugeIcon(
              icon: HugeIcons.strokeRoundedDashboardSquare01,
              color: Color(0xFF7B2FBE),
              size: 22.0,
            ),
            label: 'Dashboard',
          ),
          NavigationDestination(
            icon: HugeIcon(
              icon: HugeIcons.strokeRoundedInvoice01,
              color: Colors.white54,
              size: 22.0,
            ),
            selectedIcon: HugeIcon(
              icon: HugeIcons.strokeRoundedInvoice01,
              color: Color(0xFF7B2FBE),
              size: 22.0,
            ),
            label: 'Transactions',
          ),
        ],
      ),
    );
  }

  Widget _buildDashboard() {
    return RefreshIndicator(
      onRefresh: _checkStatus,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Status cards
          Row(children: [
            Expanded(
                child: _StatusCard(
              label: 'Service',
              value: _serviceRunning ? 'চলছে ✅' : 'বন্ধ ❌',
              icon: HugeIcons.strokeRoundedWifiSync,
              color: _serviceRunning ? Colors.green : Colors.red,
            )),
            const SizedBox(width: 12),
            Expanded(
                child: _StatusCard(
              label: 'Server',
              value: _serverOnline ? 'Online ✅' : 'Offline ❌',
              icon: HugeIcons.strokeRoundedServerStack01,
              color: _serverOnline ? Colors.green : Colors.orange,
            )),
          ]),

          const SizedBox(height: 20),

          // Control buttons
          Row(children: [
            Expanded(
              child: _ActionButton(
                label: _serviceRunning
                    ? 'Service বন্ধ করুন'
                    : 'Service চালু করুন',
                icon: _serviceRunning
                    ? HugeIcons.strokeRoundedStopCircle
                    : HugeIcons.strokeRoundedPlayCircle,
                color: _serviceRunning ? Colors.redAccent : Colors.green,
                onTap: () async {
                  final svc = FlutterBackgroundService();
                  if (_serviceRunning) {
                    svc.invoke('stopService');
                  } else {
                    await svc.startService();
                  }
                  await Future.delayed(const Duration(milliseconds: 500));
                  _checkStatus();
                },
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _ActionButton(
                label: 'Server Ping করুন',
                icon: HugeIcons.strokeRoundedWifi01,
                color: const Color(0xFF4776E6),
                onTap: _checkStatus,
              ),
            ),
          ]),

          const SizedBox(height: 24),

          // Recent transactions
          const Text('সাম্প্রতিক পেমেন্ট',
              style: TextStyle(
                  color: Colors.white70,
                  fontSize: 14,
                  fontWeight: FontWeight.w600)),
          const SizedBox(height: 10),

          if (_recent.isEmpty)
            Container(
              padding: const EdgeInsets.all(32),
              decoration: BoxDecoration(
                color: const Color(0xFF13131F),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Column(
                children: [
                  HugeIcon(
                    icon: HugeIcons.strokeRoundedInbox,
                    color: Colors.white24,
                    size: 48.0,
                  ),
                  SizedBox(height: 8),
                  Text('এখনো কোনো পেমেন্ট আসেনি',
                      style: TextStyle(color: Colors.white38)),
                ],
              ),
            )
          else
            ...(_recent.take(5).map((txn) => _TransactionTile(txn: txn))),
        ],
      ),
    );
  }
}

class _StatusCard extends StatelessWidget {
  final String label, value;
  final List<List<dynamic>> icon;
  final Color color;
  const _StatusCard(
      {required this.label,
      required this.value,
      required this.icon,
      required this.color});

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: const Color(0xFF13131F),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withValues(alpha: 0.3)),
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          HugeIcon(icon: icon, color: color, size: 24.0),
          const SizedBox(height: 8),
          Text(label,
              style: const TextStyle(color: Colors.white54, fontSize: 12)),
          Text(value,
              style: TextStyle(color: color, fontWeight: FontWeight.bold)),
        ]),
      );
}

class _ActionButton extends StatelessWidget {
  final String label;
  final List<List<dynamic>> icon;
  final Color color;
  final VoidCallback onTap;
  const _ActionButton(
      {required this.label,
      required this.icon,
      required this.color,
      required this.onTap});

  @override
  Widget build(BuildContext context) => GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 14),
          decoration: BoxDecoration(
            gradient:
                LinearGradient(colors: [color.withValues(alpha: 0.8), color]),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
            HugeIcon(icon: icon, color: Colors.white, size: 20.0),
            const SizedBox(width: 8),
            Flexible(
                child: Text(label,
                    style: const TextStyle(color: Colors.white, fontSize: 13),
                    overflow: TextOverflow.ellipsis)),
          ]),
        ),
      );
}

class _TransactionTile extends StatelessWidget {
  final Transaction txn;
  const _TransactionTile({required this.txn});

  @override
  Widget build(BuildContext context) {
    final emoji =
        {'BKASH': '🟣', 'NAGAD': '🟠', 'ROCKET': '🔵'}[txn.method] ?? '💰';
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFF13131F),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Row(children: [
        Text(emoji, style: const TextStyle(fontSize: 24)),
        const SizedBox(width: 12),
        Expanded(
          child:
              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('৳ ${txn.amount.toStringAsFixed(2)}',
                style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 16)),
            Text('${txn.method} • ${txn.senderPhone}',
                style: const TextStyle(color: Colors.white54, fontSize: 12)),
          ]),
        ),
        Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
          Text(txn.trxId,
              style: const TextStyle(color: Colors.white38, fontSize: 11)),
          HugeIcon(
            icon: txn.synced
                ? HugeIcons.strokeRoundedCloudUpload
                : HugeIcons.strokeRoundedCloudOff,
            color: txn.synced ? Colors.green : Colors.orange,
            size: 16.0,
          ),
        ]),
      ]),
    );
  }
}
