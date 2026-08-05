const express = require('express');
const router = express.Router();
const Transaction = require('../models/Transaction');

// ─── Middleware: API Key Auth ──────────────────────────────────────────────
function requireApiKey(req, res, next) {
  const key = req.headers['x-api-key'];
  if (!key || key !== process.env.API_KEY) {
    return res.status(401).json({ success: false, message: 'Unauthorized: Invalid API Key' });
  }
  next();
}

// ══════════════════════════════════════════════════════════════════════════════
// POST /api/transactions
// Receive a new transaction from the Android app
// ══════════════════════════════════════════════════════════════════════════════
router.post('/', requireApiKey, async (req, res) => {
  try {
    const { trxId, senderPhone, amount, method, rawSms, receivedAt, deviceId } = req.body;

    // Validate required fields
    if (!trxId || !senderPhone || amount === undefined || !method || !rawSms) {
      return res.status(400).json({
        success: false,
        message: 'Missing required fields: trxId, senderPhone, amount, method, rawSms',
      });
    }

    // Check for duplicate TrxID (idempotency)
    const existing = await Transaction.findOne({ trxId });
    if (existing) {
      return res.status(200).json({
        success: true,
        duplicate: true,
        message: 'Transaction already exists (idempotent)',
        data: existing,
      });
    }

    const txn = await Transaction.create({
      trxId,
      senderPhone,
      amount: parseFloat(amount),
      method: method.toUpperCase(),
      rawSms,
      receivedAt: receivedAt ? new Date(receivedAt) : new Date(),
      deviceId: deviceId || 'unknown',
      synced: true,
    });

    console.log(`[✅] New ${txn.method} transaction: ৳${txn.amount} from ${txn.senderPhone} | TrxID: ${txn.trxId}`);

    return res.status(201).json({ success: true, data: txn });
  } catch (err) {
    // Handle MongoDB duplicate key
    if (err.code === 11000) {
      return res.status(200).json({ success: true, duplicate: true, message: 'Already synced' });
    }
    console.error('[❌] Transaction save error:', err.message);
    return res.status(500).json({ success: false, message: err.message });
  }
});

// ══════════════════════════════════════════════════════════════════════════════
// GET /api/transactions
// List transactions with optional filters
// ══════════════════════════════════════════════════════════════════════════════
router.get('/', requireApiKey, async (req, res) => {
  try {
    const {
      method,
      from,   // ISO date string
      to,
      page = 1,
      limit = 50,
    } = req.query;

    const query = {};
    if (method) query.method = method.toUpperCase();
    if (from || to) {
      query.receivedAt = {};
      if (from) query.receivedAt.$gte = new Date(from);
      if (to)   query.receivedAt.$lte = new Date(to);
    }

    const [transactions, total] = await Promise.all([
      Transaction.find(query)
        .sort({ receivedAt: -1 })
        .skip((page - 1) * limit)
        .limit(parseInt(limit)),
      Transaction.countDocuments(query),
    ]);

    const totalAmount = transactions.reduce((sum, t) => sum + t.amount, 0);

    return res.json({
      success: true,
      pagination: { page: parseInt(page), limit: parseInt(limit), total },
      summary: { count: transactions.length, totalAmount: totalAmount.toFixed(2) },
      data: transactions,
    });
  } catch (err) {
    return res.status(500).json({ success: false, message: err.message });
  }
});

// ══════════════════════════════════════════════════════════════════════════════
// GET /api/transactions/stats
// Daily/Monthly stats
// ══════════════════════════════════════════════════════════════════════════════
router.get('/stats', requireApiKey, async (req, res) => {
  try {
    const stats = await Transaction.aggregate([
      {
        $group: {
          _id: '$method',
          count: { $sum: 1 },
          totalAmount: { $sum: '$amount' },
          lastReceived: { $max: '$receivedAt' },
        },
      },
      { $sort: { totalAmount: -1 } },
    ]);

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const todayStats = await Transaction.aggregate([
      { $match: { receivedAt: { $gte: today } } },
      { $group: { _id: null, count: { $sum: 1 }, totalAmount: { $sum: '$amount' } } },
    ]);

    return res.json({
      success: true,
      byMethod: stats,
      today: todayStats[0] || { count: 0, totalAmount: 0 },
    });
  } catch (err) {
    return res.status(500).json({ success: false, message: err.message });
  }
});

module.exports = router;
