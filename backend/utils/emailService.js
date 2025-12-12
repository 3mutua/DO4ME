const nodemailer = require('nodemailer');

const createTransporter = () => {
  return nodemailer.createTransporter({
    service: process.env.EMAIL_SERVICE,
    auth: {
      user: process.env.EMAIL_USER,
      pass: process.env.EMAIL_PASS
    }
  });
};

exports.sendWelcomeEmail = async (user) => {
  try {
    const transporter = createTransporter();
    
    const mailOptions = {
      from: process.env.EMAIL_USER,
      to: user.email,
      subject: 'Welcome to DO4ME!',
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
          <h2 style="color: #333;">Welcome to DO4ME, ${user.firstName}!</h2>
          <p>We're excited to have you on board. Your account has been successfully created.</p>
          <p>You can now start ${user.role === 'client' ? 'posting tasks' : 'finding work'} on our platform.</p>
          <div style="margin: 30px 0;">
            <a href="${process.env.CLIENT_URL}/tasks" 
               style="background-color: #007bff; color: white; padding: 12px 24px; 
                      text-decoration: none; border-radius: 5px; display: inline-block;">
              Get Started
            </a>
          </div>
          <p>If you have any questions, feel free to contact our support team.</p>
          <p>Best regards,<br>The DO4ME Team</p>
        </div>
      `
    };

    await transporter.sendMail(mailOptions);
    console.log('Welcome email sent to:', user.email);
  } catch (error) {
    console.error('Error sending welcome email:', error);
  }
};

exports.sendTaskNotification = async (user, task, notificationType) => {
  try {
    const transporter = createTransporter();
    
    let subject, message;
    
    switch (notificationType) {
      case 'proposal_received':
        subject = `New Proposal for Your Task: ${task.title}`;
        message = `You have received a new proposal for your task "${task.title}".`;
        break;
      case 'proposal_accepted':
        subject = `Your Proposal Was Accepted!`;
        message = `Your proposal for "${task.title}" has been accepted by the client.`;
        break;
      case 'task_completed':
        subject = `Task Completed: ${task.title}`;
        message = `The task "${task.title}" has been marked as completed.`;
        break;
      default:
        return;
    }

    const mailOptions = {
      from: process.env.EMAIL_USER,
      to: user.email,
      subject,
      html: `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
          <h2 style="color: #333;">${subject}</h2>
          <p>${message}</p>
          <div style="margin: 30px 0;">
            <a href="${process.env.CLIENT_URL}/tasks/${task._id}" 
               style="background-color: #007bff; color: white; padding: 12px 24px; 
                      text-decoration: none; border-radius: 5px; display: inline-block;">
              View Task
            </a>
          </div>
          <p>Best regards,<br>The DO4ME Team</p>
        </div>
      `
    };

    await transporter.sendMail(mailOptions);
    console.log('Task notification sent to:', user.email);
  } catch (error) {
    console.error('Error sending task notification:', error);
  }
};