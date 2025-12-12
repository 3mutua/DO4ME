const mongoose = require('mongoose');
const bcrypt = require('bcryptjs');
require('dotenv').config();

// Import models
const User = require('../models/User');
const Task = require('../models/Task');
const Payment = require('../models/Payment');

const seedData = async () => {
  try {
    console.log('Connecting to MongoDB...');
    
    // Connect to MongoDB with updated options
    await mongoose.connect(process.env.MONGODB_URI, {
      useNewUrlParser: true,
      useUnifiedTopology: true,
    });
    
    console.log('Connected to MongoDB successfully');

    // Clear existing data
    console.log('Clearing existing data...');
    await User.deleteMany({});
    await Task.deleteMany({});
    await Payment.deleteMany({});
    console.log('Cleared existing data');

    // Create sample users with hashed passwords
    console.log('Creating sample users...');
    
    const saltRounds = 12;
    const clientPassword = await bcrypt.hash('password123', saltRounds);
    const freelancerPassword = await bcrypt.hash('password123', saltRounds);
    const adminPassword = await bcrypt.hash('admin123', saltRounds);

    const client = await User.create({
      firstName: 'John',
      lastName: 'Client',
      email: 'client@example.com',
      password: clientPassword,
      role: 'client',
      phone: '+1234567890',
      country: 'USA',
      isVerified: true,
      isApproved: true,
      walletBalance: 500.00
    });

    const freelancer = await User.create({
      firstName: 'Jane',
      lastName: 'Freelancer',
      email: 'freelancer@example.com',
      password: freelancerPassword,
      role: 'freelancer',
      phone: '+1234567891',
      country: 'UK',
      skills: ['JavaScript', 'React', 'Node.js', 'MongoDB'],
      isVerified: true,
      isApproved: true,
      completedTasksCount: 15,
      averageRating: 4.8,
      walletBalance: 1250.50
    });

    const admin = await User.create({
      firstName: 'Admin',
      lastName: 'User',
      email: 'tech2tech254@gmail.com',
      password: 'manu@1310',
      role: 'admin',
      phone: '+254716121226',
      country: 'Kenya',
      isVerified: true,
      isApproved: true
    });

    // Create sample tasks
    console.log('Creating sample tasks...');
    
    const task1 = await Task.create({
      title: 'Website Development for E-commerce Store',
      description: 'I need a professional e-commerce website built for my clothing store. The website should be responsive, have product categories, shopping cart, and payment integration.',
      client: client._id,
      budget: 1500.00,
      category: 'programming',
      urgency: 'high',
      durationDays: 21,
      status: 'open'
    });

    const task2 = await Task.create({
      title: 'Logo Design for Tech Startup',
      description: 'Looking for a creative logo design for our new tech startup called "InnovateTech". We want something modern, minimalistic, and tech-oriented.',
      client: client._id,
      budget: 300.00,
      category: 'design',
      urgency: 'medium',
      durationDays: 7,
      status: 'open'
    });

    const task3 = await Task.create({
      title: 'Content Writing for Blog - Technology',
      description: 'Need 10 high-quality blog articles about emerging technologies (AI, Blockchain, IoT). Each article should be 1500-2000 words, SEO optimized.',
      client: client._id,
      budget: 500.00,
      category: 'writing',
      urgency: 'low',
      durationDays: 14,
      status: 'assigned',
      freelancer: freelancer._id,
      assignedAt: new Date()
    });

    console.log('\n✅ Sample data created successfully!');
    console.log('\n📊 Created Data Summary:');
    console.log('─────────────────────');
    console.log(`👥 Users: 3 (Client, Freelancer, Admin)`);
    console.log(`📝 Tasks: 3 (2 Open, 1 Assigned)`);
    
    console.log('\n🔑 Login Credentials:');
    console.log('──────────────────');
    console.log('Client:     email: client@example.com     password: password123');
    console.log('Freelancer: email: freelancer@example.com password: password123');
    console.log('Admin:      email: tech2tech254@gmail.com     password: manu@1310');

    // Close connection
    await mongoose.connection.close();
    console.log('\nDatabase connection closed.');
    
    process.exit(0);
  } catch (error) {
    console.error('\n❌ Error seeding data:', error);
    process.exit(1);
  }
};

seedData();