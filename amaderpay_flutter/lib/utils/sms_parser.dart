import '../models/transaction.dart';

/// Parses raw SMS text into a Transaction using strict regex
/// Supports: bKash, Nagad, Rocket
class SmsParser {
  // ─── bKash ────────────────────────────────────────────────────────────
  // Example: "You have received Tk 500.00 from 01XXXXXXXXX. TrxID AB1234567890"
  static final _bkashRegex = RegExp(
    r'You have received Tk\s*([\d,]+\.?\d*)\s*from\s*(\+?01\d{9})\s*\.\s*TrxID\s*([A-Z0-9]+)',
    caseSensitive: false,
  );

  // ─── Nagad ────────────────────────────────────────────────────────────
  // Example: "Cash In Tk 200.00 from 01XXXXXXXXX TxnID: ND1234567890"
  static final _nagadRegex = RegExp(
    r'(?:Cash In|Received)\s*Tk\s*([\d,]+\.?\d*)\s*from\s*(\+?01\d{9})\s*TxnID[:\s]+([A-Z0-9]+)',
    caseSensitive: false,
  );

  // ─── Rocket ───────────────────────────────────────────────────────────
  // Example: "Tk 150.00 received from 01XXXXXXXXX TrxnID RK12345678"
  static final _rocketRegex = RegExp(
    r'Tk\s*([\d,]+\.?\d*)\s*received from\s*(\+?01\d{9})\s*TrxnID\s+([A-Z0-9]+)',
    caseSensitive: false,
  );

  /// Returns a [Transaction] if the SMS matches any pattern, otherwise null.
  static Transaction? parse(String smsBody, String senderId) {
    // Normalise: remove commas from numbers
    final body = smsBody.replaceAll(',', '');

    // Try bKash
    final bkash = _bkashRegex.firstMatch(body);
    if (bkash != null) {
      return _build(
        amount: bkash.group(1)!,
        sender: bkash.group(2)!,
        trxId: bkash.group(3)!,
        method: 'BKASH',
        rawSms: smsBody,
      );
    }

    // Try Nagad
    final nagad = _nagadRegex.firstMatch(body);
    if (nagad != null) {
      return _build(
        amount: nagad.group(1)!,
        sender: nagad.group(2)!,
        trxId: nagad.group(3)!,
        method: 'NAGAD',
        rawSms: smsBody,
      );
    }

    // Try Rocket
    final rocket = _rocketRegex.firstMatch(body);
    if (rocket != null) {
      return _build(
        amount: rocket.group(1)!,
        sender: rocket.group(2)!,
        trxId: rocket.group(3)!,
        method: 'ROCKET',
        rawSms: smsBody,
      );
    }

    return null; // Not a payment SMS
  }

  static Transaction _build({
    required String amount,
    required String sender,
    required String trxId,
    required String method,
    required String rawSms,
  }) =>
      Transaction(
        id: '${trxId}_${DateTime.now().millisecondsSinceEpoch}',
        trxId: trxId,
        senderPhone: sender.trim(),
        amount: double.tryParse(amount.trim()) ?? 0.0,
        method: method,
        rawSms: rawSms,
        receivedAt: DateTime.now(),
      );
}
