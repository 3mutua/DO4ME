const mongoose = require('mongoose');

const taskSchema = new mongoose.Schema({
  title: {
    type: String,
    required: [true, 'Task title is required'],
    trim: true,
    maxlength: [200, 'Title cannot exceed 200 characters']
  },
  description: {
    type: String,
    required: [true, 'Task description is required'],
    maxlength: [5000, 'Description cannot exceed 5000 characters']
  },
  client: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: [true, 'Client is required']
  },
  freelancer: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    default: null
  },
  budget: {
    type: Number,
    required: [true, 'Budget is required'],
    min: [0.01, 'Budget must be greater than 0']
  },
  platformFee: {
    type: Number,
    default: 0.00
  },
  transactionFee: {
    type: Number,
    default: 0.00
  },
  status: {
    type: String,
    enum: ['draft', 'open', 'assigned', 'in_progress', 'completed', 'cancelled', 'disputed'],
    default: 'draft'
  },
  category: {
    type: String,
    enum: ['writing', 'design', 'programming', 'marketing', 'data_entry', 'customer_service', 'other'],
    default: 'other'
  },
  urgency: {
    type: String,
    enum: ['low', 'medium', 'high'],
    default: 'medium'
  },
  durationDays: {
    type: Number,
    default: 7,
    min: [1, 'Duration must be at least 1 day']
  },
  attachments: [{
    name: String,
    url: String,
    size: Number,
    uploadDate: {
      type: Date,
      default: Date.now
    }
  }],
  proposals: [{
    freelancer: {
      type: mongoose.Schema.Types.ObjectId,
      ref: 'User',
      required: true
    },
    bidAmount: {
      type: Number,
      required: true,
      min: [0.01, 'Bid amount must be greater than 0']
    },
    coverLetter: {
      type: String,
      required: true,
      maxlength: [2000, 'Cover letter cannot exceed 2000 characters']
    },
    estimatedDays: {
      type: Number,
      required: true,
      min: [1, 'Estimated days must be at least 1']
    },
    status: {
      type: String,
      enum: ['pending', 'accepted', 'rejected'],
      default: 'pending'
    },
    submittedAt: {
      type: Date,
      default: Date.now
    }
  }],
  milestones: [{
    title: {
      type: String,
      required: true,
      maxlength: 200
    },
    description: {
      type: String,
      maxlength: 1000
    },
    amount: {
      type: Number,
      required: true,
      min: [0.01, 'Milestone amount must be greater than 0']
    },
    status: {
      type: String,
      enum: ['pending', 'completed', 'approved'],
      default: 'pending'
    },
    dueDate: Date,
    completedAt: Date,
    createdAt: {
      type: Date,
      default: Date.now
    }
  }],
  assignedAt: Date,
  completedAt: Date
}, {
  timestamps: true
});

// Indexes
taskSchema.index({ status: 1 });
taskSchema.index({ category: 1 });
taskSchema.index({ client: 1 });
taskSchema.index({ freelancer: 1 });
taskSchema.index({ createdAt: -1 });
taskSchema.index({ 'proposals.freelancer': 1 });

// Virtual for total budget including fees
taskSchema.virtual('totalAmount').get(function() {
  return this.budget + this.platformFee + this.transactionFee;
});

// Methods
taskSchema.methods.isClient = function(userId) {
  return this.client.toString() === userId.toString();
};

taskSchema.methods.isFreelancer = function(userId) {
  return this.freelancer && this.freelancer.toString() === userId.toString();
};

taskSchema.methods.canBeModifiedBy = function(user) {
  if (user.role === 'admin') return true;
  if (this.isClient(user._id)) return true;
  return false;
};

module.exports = mongoose.model('Task', taskSchema);