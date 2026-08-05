import 'dart:ui';
import 'package:flutter_background_service/flutter_background_service.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:telephony/telephony.dart';
import '../models/transaction.dart';
import '../utils/sms_parser.dart';
import 'api_service.dart';

// ─── Notification channel IDs ──────────────────────────────────────────────
const kForegroundChannelId = 'bkash_gateway_foreground_channel';
const kPaymentChannelId = 'bkash_gateway_notifications';
const kForegroundNotifId = 888;

final _notifications = FlutterLocalNotificationsPlugin();
final _api = ApiService();
final _telephony = Telephony.instance;

/// Initialise and start the background service
Future<void> initBackgroundService() async {
  final service = FlutterBackgroundService();

  // Notification channels
  const AndroidNotificationChannel foregroundChannel = AndroidNotificationChannel(
    kForegroundChannelId,
    'Gateway Service',
    description: 'NexoraPay SMS Gateway is actively listening',
    importance: Importance.low,
  );
  const AndroidNotificationChannel paymentChannel = AndroidNotificationChannel(
    kPaymentChannelId,
    'Payment Notifications',
    description: 'Alerts when a payment is received',
    importance: Importance.high,
  );

  await _notifications
      .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
      ?.createNotificationChannel(foregroundChannel);
  await _notifications
      .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
      ?.createNotificationChannel(paymentChannel);

  await _notifications.initialize(const InitializationSettings(
    android: AndroidInitializationSettings('@mipmap/ic_launcher'),
  ));

  await service.configure(
    androidConfiguration: AndroidConfiguration(
      onStart: _onServiceStart,
      autoStart: true,
      isForegroundMode: true,
      notificationChannelId: kForegroundChannelId,
      initialNotificationTitle: 'bKash SMS Gateway 24/7 Active',
      initialNotificationContent: 'Listening for payment SMS & syncing with server...',
      foregroundServiceNotificationId: kForegroundNotifId,
      // Android 14+: specify foreground service type
      foregroundServiceTypes: [AndroidForegroundType.dataSync],
    ),
    iosConfiguration: IosConfiguration(autoStart: false),
  );

  await service.startService();
}

/// Background isolate entry point
@pragma('vm:entry-point')
void _onServiceStart(ServiceInstance service) async {
  DartPluginRegistrant.ensureInitialized();

  _telephony.listenIncomingSms(
    onNewMessage: (SmsMessage sms) async {
      final body = sms.body ?? '';
      final sender = sms.address ?? '';
      print('[GatewayService] SMS from $sender: $body');

      final txn = SmsParser.parse(body, sender);
      if (txn != null) {
        print('[GatewayService] ✅ Parsed: ${txn.method} TrxID=${txn.trxId} ৳${txn.amount}');
        _showPaymentNotification(txn);
        await _api.sendTransaction(txn);
        service.invoke('transaction_received', txn.toJson());
      } else {
        print('[GatewayService] ⚠ SMS did not match any payment pattern.');
      }
    },
    onBackgroundMessage: _backgroundSmsHandler,
    listenInBackground: true,
  );

  // Retry any failed transactions every 5 minutes
  Stream.periodic(const Duration(minutes: 5)).listen((_) async {
    await _api.retryPending();
  });
}

/// Called when SMS arrives while app is killed
@pragma('vm:entry-point')
Future<void> _backgroundSmsHandler(SmsMessage sms) async {
  final txn = SmsParser.parse(sms.body ?? '', sms.address ?? '');
  if (txn != null) {
    await _api.sendTransaction(txn);
    _showPaymentNotification(txn);
  }
}

/// Shows a rich notification when a payment is detected
void _showPaymentNotification(Transaction txn) {
  final methodEmoji = {'BKASH': '🟣', 'NAGAD': '🟠', 'ROCKET': '🔵'}[txn.method] ?? '💰';
  _notifications.show(
    txn.trxId.hashCode,
    '$methodEmoji ${txn.method} পেমেন্ট সিঙ্ক হয়েছে!',
    '৳ ${txn.amount.toStringAsFixed(2)} পাওয়া গেছে (${txn.senderPhone}). TrxID: ${txn.trxId}',
    const NotificationDetails(
      android: AndroidNotificationDetails(
        kPaymentChannelId,
        'Payment Notifications',
        importance: Importance.high,
        priority: Priority.high,
        icon: '@mipmap/ic_launcher',
        autoCancel: true,
      ),
    ),
  );
}
