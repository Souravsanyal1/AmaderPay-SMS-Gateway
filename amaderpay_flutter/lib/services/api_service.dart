import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/transaction.dart';

/// Sends parsed transactions to the configured backend server
class ApiService {
  static const _baseUrlKey = 'server_url';
  static const _apiKeyKey = 'api_key';
  static const _pendingKey = 'pending_transactions';

  late Dio _dio;

  ApiService() {
    _dio = Dio(BaseOptions(
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 15),
      headers: {'Content-Type': 'application/json'},
    ));

    // Add logging interceptor
    _dio.interceptors.add(LogInterceptor(
      requestBody: true,
      responseBody: true,
      logPrint: (obj) => print('[ApiService] $obj'),
    ));
  }

  // ─── Settings ─────────────────────────────────────────────────────────

  Future<String> getServerUrl() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_baseUrlKey) ?? 'http://localhost:3000';
  }

  Future<String> getApiKey() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_apiKeyKey) ?? '';
  }

  Future<void> saveSettings({required String serverUrl, required String apiKey}) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_baseUrlKey, serverUrl.trim());
    await prefs.setString(_apiKeyKey, apiKey.trim());
  }

  // ─── Send Transaction ─────────────────────────────────────────────────

  /// Sends a transaction to the backend. Returns true on success.
  Future<bool> sendTransaction(Transaction txn) async {
    try {
      final url = await getServerUrl();
      final apiKey = await getApiKey();

      final response = await _dio.post(
        '$url/api/transactions',
        data: txn.toJson(),
        options: Options(headers: {'x-api-key': apiKey}),
      );

      return response.statusCode == 200 || response.statusCode == 201;
    } on DioException catch (e) {
      print('[ApiService] Send failed: ${e.message}');
      await _savePending(txn); // Queue for retry
      return false;
    }
  }

  // ─── Retry Pending ────────────────────────────────────────────────────

  Future<void> retryPending() async {
    final pending = await _loadPending();
    if (pending.isEmpty) return;

    final List<Transaction> failed = [];
    for (final txn in pending) {
      final ok = await sendTransaction(txn);
      if (!ok) failed.add(txn);
    }
    await _savePendingList(failed);
  }

  // ─── Health Check ─────────────────────────────────────────────────────

  Future<bool> pingServer() async {
    try {
      final url = await getServerUrl();
      final res = await _dio.get('$url/health');
      return res.statusCode == 200;
    } catch (_) {
      return false;
    }
  }

  // ─── Persistence helpers ──────────────────────────────────────────────

  Future<void> _savePending(Transaction txn) async {
    final pending = await _loadPending();
    pending.add(txn);
    await _savePendingList(pending);
  }

  Future<List<Transaction>> _loadPending() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getStringList(_pendingKey) ?? [];
    return raw
        .map((e) => Transaction.fromJson(jsonDecode(e)))
        .toList();
  }

  Future<void> _savePendingList(List<Transaction> list) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList(
      _pendingKey,
      list.map((e) => jsonEncode(e.toJson())).toList(),
    );
  }
}
