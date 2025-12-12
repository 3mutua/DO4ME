const mongoose = require('mongoose');

const paymentSchema = new mongoose.Schema({
  user: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  task: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Task'
  },
  amount: {
    type: Number,
    required: true,
    min: [0.01, 'Amount must be greater than 0']
  },
  currency: {
    type: String,
    default: 'USD',
    uppercase: true,
    length: 3
  },
  paymentMethod: {
    type: String,
    enum: ['stripe', 'mpesa', 'paypal', 'flutterwave', 'wallet'],
    required: true
  },
  gatewayReference: {
    type: String,
    required: true
  },
  status: {
    type: String,
    enum: ['pending', 'completed', 'failed', 'refunded'],
    default: 'pending'
  },
  metadata: {
    type: mongoose.Schema.Types.Mixed,
    default: {}
  },
  taskSnapshot: {
    title: String,
    budget: Number
  },
  userSnapshot: {
    firstName: String,
    lastName: String
  },
  completedAt: Date
}, {
  timestamps: true
});

// Indexes
paymentSchema.index({ gatewayReference: 1 });
paymentSchema.index({ status: 1 });
paymentSchema.index({ user: 1, createdAt: -1 });
paymentSchema.index({ createdAt: -1 });

module.exports = mongoose.model('Payment', paymentSchema);