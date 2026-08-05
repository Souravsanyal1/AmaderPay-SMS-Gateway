/// Transaction model — parsed from raw SMS
class Transaction {
  final String id;
  final String trxId;
  final String senderPhone;
  final double amount;
  final String method; // BKASH, NAGAD, ROCKET
  final String rawSms;
  final DateTime receivedAt;
  final bool synced;

  Transaction({
    required this.id,
    required this.trxId,
    required this.senderPhone,
    required this.amount,
    required this.method,
    required this.rawSms,
    required this.receivedAt,
    this.synced = false,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'trxId': trxId,
        'senderPhone': senderPhone,
        'amount': amount,
        'method': method,
        'rawSms': rawSms,
        'receivedAt': receivedAt.toIso8601String(),
        'synced': synced,
      };

  factory Transaction.fromJson(Map<String, dynamic> json) => Transaction(
        id: json['id'] ?? '',
        trxId: json['trxId'] ?? '',
        senderPhone: json['senderPhone'] ?? '',
        amount: (json['amount'] as num?)?.toDouble() ?? 0.0,
        method: json['method'] ?? '',
        rawSms: json['rawSms'] ?? '',
        receivedAt: DateTime.tryParse(json['receivedAt'] ?? '') ?? DateTime.now(),
        synced: json['synced'] ?? false,
      );

  Transaction copyWith({bool? synced}) => Transaction(
        id: id,
        trxId: trxId,
        senderPhone: senderPhone,
        amount: amount,
        method: method,
        rawSms: rawSms,
        receivedAt: receivedAt,
        synced: synced ?? this.synced,
      );
}
