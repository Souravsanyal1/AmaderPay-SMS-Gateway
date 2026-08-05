const mongoose = require('mongoose');

const transactionSchema = new mongoose.Schema(
  {
    trxId: {
      type: String,
      required: true,
      unique: true,
      index: true,
    },
    senderPhone: {
      type: String,
      required: true,
      trim: true,
    },
    amount: {
      type: Number,
      required: true,
      min: 0,
    },
    method: {
      type: String,
      required: true,
      enum: ['BKASH', 'NAGAD', 'ROCKET'],
      uppercase: true,
    },
    rawSms: {
      type: String,
      required: true,
    },
    receivedAt: {
      type: Date,
      default: Date.now,
    },
    synced: {
      type: Boolean,
      default: true, // Synced = true once it reaches the server
    },
    deviceId: {
      type: String, // Optional: identify which device sent this
      default: 'unknown',
    },
  },
  {
    timestamps: true, // adds createdAt, updatedAt
  }
);

// Index for fast date-range queries
transactionSchema.index({ receivedAt: -1 });
transactionSchema.index({ method: 1, receivedAt: -1 });

module.exports = mongoose.model('Transaction', transactionSchema);
