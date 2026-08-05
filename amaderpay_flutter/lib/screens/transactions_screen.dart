import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/transaction.dart';

class TransactionsScreen extends StatefulWidget {
  const TransactionsScreen({super.key});
  @override
  State<TransactionsScreen> createState() => _TransactionsScreenState();
}

class _TransactionsScreenState extends State<TransactionsScreen> {
  List<Transaction> _all = [];
  String _filter = 'ALL';
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getStringList('transaction_history') ?? [];
    setState(() {
      _all = raw.map((e) => Transaction.fromJson(jsonDecode(e))).toList();
      _loading = false;
    });
  }

  List<Transaction> get _filtered => _filter == 'ALL'
      ? _all
      : _all.where((t) => t.method == _filter).toList();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0D0D1A),
      body: Column(children: [
        // Filter chips
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: ['ALL', 'BKASH', 'NAGAD', 'ROCKET'].map((m) {
                final active = _filter == m;
                final colors = {
                  'BKASH': const Color(0xFF7B2FBE),
                  'NAGAD': const Color(0xFFE97B2B),
                  'ROCKET': const Color(0xFF1D6EFF),
                  'ALL': const Color(0xFF444466),
                };
                return GestureDetector(
                  onTap: () => setState(() => _filter = m),
                  child: Container(
                    margin: const EdgeInsets.only(right: 8),
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    decoration: BoxDecoration(
                      color: active ? colors[m] : const Color(0xFF13131F),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: active ? Colors.transparent : Colors.white12),
                    ),
                    child: Text(m, style: TextStyle(
                      color: active ? Colors.white : Colors.white54,
                      fontWeight: active ? FontWeight.bold : FontWeight.normal,
                    )),
                  ),
                );
              }).toList(),
            ),
          ),
        ),

        // Summary row
        if (_filtered.isNotEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Row(children: [
              Text('${_filtered.length} টি লেনদেন',
                  style: const TextStyle(color: Colors.white54, fontSize: 12)),
              const Spacer(),
              Text(
                'মোট: ৳ ${_filtered.fold(0.0, (s, t) => s + t.amount).toStringAsFixed(2)}',
                style: const TextStyle(color: Colors.greenAccent, fontSize: 12, fontWeight: FontWeight.bold),
              ),
            ]),
          ),

        // Transaction list
        Expanded(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : _filtered.isEmpty
                  ? Center(
                      child: Column(mainAxisSize: MainAxisSize.min, children: [
                        Icon(Icons.receipt_long, color: Colors.white12, size: 64),
                        const SizedBox(height: 12),
                        Text('কোনো লেনদেন নেই', style: TextStyle(color: Colors.white24)),
                      ]),
                    )
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.builder(
                        padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                        itemCount: _filtered.length,
                        itemBuilder: (ctx, i) => _TxnCard(txn: _filtered[i]),
                      ),
                    ),
        ),
      ]),
    );
  }
}

class _TxnCard extends StatelessWidget {
  final Transaction txn;
  const _TxnCard({required this.txn});

  @override
  Widget build(BuildContext context) {
    final emoji = {'BKASH': '🟣', 'NAGAD': '🟠', 'ROCKET': '🔵'}[txn.method] ?? '💰';
    final methodColor = {
      'BKASH': const Color(0xFF7B2FBE),
      'NAGAD': const Color(0xFFE97B2B),
      'ROCKET': const Color(0xFF1D6EFF),
    }[txn.method] ?? Colors.grey;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFF13131F),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: methodColor.withOpacity(0.15)),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Text(emoji, style: const TextStyle(fontSize: 22)),
          const SizedBox(width: 10),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('৳ ${txn.amount.toStringAsFixed(2)}',
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18)),
              Text(txn.senderPhone, style: const TextStyle(color: Colors.white54, fontSize: 12)),
            ]),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: methodColor.withOpacity(0.2),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(txn.method, style: TextStyle(color: methodColor, fontSize: 11, fontWeight: FontWeight.bold)),
          ),
        ]),
        const Divider(color: Colors.white10, height: 20),
        Row(children: [
          Icon(Icons.tag, size: 14, color: Colors.white38),
          const SizedBox(width: 4),
          Text(txn.trxId, style: const TextStyle(color: Colors.white38, fontSize: 12)),
          const Spacer(),
          Icon(
            txn.synced ? Icons.cloud_done : Icons.cloud_off,
            size: 14,
            color: txn.synced ? Colors.green : Colors.orange,
          ),
          const SizedBox(width: 4),
          Text(
            txn.synced ? 'Synced' : 'Pending',
            style: TextStyle(
              color: txn.synced ? Colors.green : Colors.orange,
              fontSize: 11,
            ),
          ),
        ]),
        const SizedBox(height: 4),
        Text(
          '${txn.receivedAt.day}/${txn.receivedAt.month}/${txn.receivedAt.year} ${txn.receivedAt.hour}:${txn.receivedAt.minute.toString().padLeft(2, '0')}',
          style: const TextStyle(color: Colors.white24, fontSize: 11),
        ),
      ]),
    );
  }
}
