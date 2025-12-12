const Task = require('../models/Task');
const User = require('../models/User');

// @desc    Create a task
// @route   POST /api/tasks
// @access  Private (Client)
exports.createTask = async (req, res) => {
  try {
    const {
      title,
      description,
      budget,
      category,
      urgency,
      durationDays,
      attachments,
      milestones
    } = req.body;

    const task = await Task.create({
      title,
      description,
      client: req.user.id,
      budget,
      category,
      urgency,
      durationDays,
      attachments: attachments || [],
      milestones: milestones || []
    });

    // Populate client info
    await task.populate('client', 'firstName lastName country averageRating');

    res.status(201).json({
      success: true,
      message: 'Task created successfully',
      data: { task }
    });
  } catch (error) {
    console.error('Create task error:', error);
    res.status(500).json({
      success: false,
      message: 'Error creating task',
      error: error.message
    });
  }
};

// @desc    Get all tasks with filtering and pagination
// @route   GET /api/tasks
// @access  Public
exports.getTasks = async (req, res) => {
  try {
    const {
      page = 1,
      limit = 10,
      status,
      category,
      minBudget,
      maxBudget,
      urgency,
      search
    } = req.query;

    // Build query
    let query = {};
    
    if (status) query.status = status;
    if (category) query.category = category;
    if (urgency) query.urgency = urgency;
    
    if (minBudget || maxBudget) {
      query.budget = {};
      if (minBudget) query.budget.$gte = parseFloat(minBudget);
      if (maxBudget) query.budget.$lte = parseFloat(maxBudget);
    }
    
    if (search) {
      query.$or = [
        { title: { $regex: search, $options: 'i' } },
        { description: { $regex: search, $options: 'i' } }
      ];
    }

    // Only show open tasks to non-owners
    if (!req.user || req.user.role !== 'admin') {
      query.status = { $in: ['open', 'assigned', 'in_progress'] };
    }

    const tasks = await Task.find(query)
      .populate('client', 'firstName lastName country averageRating profilePicture')
      .populate('freelancer', 'firstName lastName country averageRating profilePicture')
      .sort({ createdAt: -1 })
      .limit(limit * 1)
      .skip((page - 1) * limit);

    const total = await Task.countDocuments(query);

    res.json({
      success: true,
      data: {
        tasks,
        totalPages: Math.ceil(total / limit),
        currentPage: page,
        total
      }
    });
  } catch (error) {
    console.error('Get tasks error:', error);
    res.status(500).json({
      success: false,
      message: 'Error fetching tasks',
      error: error.message
    });
  }
};

// @desc    Get single task
// @route   GET /api/tasks/:id
// @access  Public
exports.getTask = async (req, res) => {
  try {
    const task = await Task.findById(req.params.id)
      .populate('client', 'firstName lastName country averageRating profilePicture completedTasksCount')
      .populate('freelancer', 'firstName lastName country averageRating profilePicture completedTasksCount')
      .populate('proposals.freelancer', 'firstName lastName country averageRating profilePicture completedTasksCount');

    if (!task) {
      return res.status(404).json({
        success: false,
        message: 'Task not found'
      });
    }

    res.json({
      success: true,
      data: { task }
    });
  } catch (error) {
    console.error('Get task error:', error);
    res.status(500).json({
      success: false,
      message: 'Error fetching task',
      error: error.message
    });
  }
};

// @desc    Submit proposal
// @route   POST /api/tasks/:id/proposals
// @access  Private (Freelancer)
exports.submitProposal = async (req, res) => {
  try {
    const { bidAmount, coverLetter, estimatedDays } = req.body;
    const taskId = req.params.id;
    const freelancerId = req.user.id;

    const task = await Task.findById(taskId);
    if (!task) {
      return res.status(404).json({
        success: false,
        message: 'Task not found'
      });
    }

    // Check if task is open
    if (task.status !== 'open') {
      return res.status(400).json({
        success: false,
        message: 'Task is not open for proposals'
      });
    }

    // Check if freelancer is also the client
    if (task.client.toString() === freelancerId) {
      return res.status(400).json({
        success: false,
        message: 'You cannot submit a proposal for your own task'
      });
    }

    // Check if already submitted
    const existingProposal = task.proposals.find(
      p => p.freelancer.toString() === freelancerId
    );
    if (existingProposal) {
      return res.status(400).json({
        success: false,
        message: 'You have already submitted a proposal for this task'
      });
    }

    // Add proposal
    task.proposals.push({
      freelancer: freelancerId,
      bidAmount,
      coverLetter,
      estimatedDays
    });

    await task.save();
    await task.populate('proposals.freelancer', 'firstName lastName country averageRating profilePicture');

    res.status(201).json({
      success: true,
      message: 'Proposal submitted successfully',
      data: { task }
    });
  } catch (error) {
    console.error('Submit proposal error:', error);
    res.status(500).json({
      success: false,
      message: 'Error submitting proposal',
      error: error.message
    });
  }
};