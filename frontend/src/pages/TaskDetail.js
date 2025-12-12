import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { Container, Row, Col, Card, Button, Form, Spinner, Alert, Tab, Tabs } from 'react-bootstrap';
import { useAuth } from '../contexts/AuthContext';
import axios from 'axios';

const TaskDetail = () => {
  const { id } = useParams();
  const [task, setTask] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [proposal, setProposal] = useState({
    bidAmount: '',
    coverLetter: '',
    estimatedDays: ''
  });
  const [submitting, setSubmitting] = useState(false);
  const { user, isAuthenticated } = useAuth();

  useEffect(() => {
    fetchTask();
  }, [id]);

  const fetchTask = async () => {
    try {
      const res = await axios.get(`/api/tasks/${id}`);
      setTask(res.data.data.task);
    } catch (error) {
      setError('Error fetching task details');
    } finally {
      setLoading(false);
    }
  };

  const handleProposalChange = (e) => {
    setProposal({
      ...proposal,
      [e.target.name]: e.target.value
    });
  };

  const submitProposal = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      await axios.post(`/api/tasks/${id}/proposals`, proposal);
      alert('Proposal submitted successfully!');
      setProposal({ bidAmount: '', coverLetter: '', estimatedDays: '' });
      fetchTask(); // Refresh task to show the proposal
    } catch (error) {
      setError(error.response?.data?.message || 'Error submitting proposal');
    } finally {
      setSubmitting(false);
    }
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(amount);
  };

  if (loading) {
    return (
      <Container className="text-center py-5">
        <Spinner animation="border" role="status">
          <span className="visually-hidden">Loading...</span>
        </Spinner>
      </Container>
    );
  }

  if (!task) {
    return (
      <Container>
        <Alert variant="danger">Task not found</Alert>
      </Container>
    );
  }

  const hasSubmittedProposal = isAuthenticated && 
    task.proposals.some(p => p.freelancer._id === user._id);

  return (
    <Container>
      <Row>
        <Col lg={8}>
          <Card className="mb-4">
            <Card.Body>
              <div className="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <h1>{task.title}</h1>
                  <div className="d-flex gap-2 mb-2">
                    <span className={`badge bg-${
                      task.urgency === 'high' ? 'danger' : 
                      task.urgency === 'medium' ? 'warning' : 'success'
                    }`}>
                      {task.urgency}
                    </span>
                    <span className="badge bg-secondary">{task.category}</span>
                    <span className="badge bg-info">{task.status}</span>
                  </div>
                </div>
                <h3>{formatCurrency(task.budget)}</h3>
              </div>

              <p className="text-muted">
                Posted by {task.client.firstName} {task.client.lastName} • {new Date(task.createdAt).toLocaleDateString()}
              </p>

              <div className="mb-4">
                <h5>Description</h5>
                <p>{task.description}</p>
              </div>

              <Row className="mb-4">
                <Col md={6}>
                  <strong>Duration:</strong> {task.durationDays} days
                </Col>
                <Col md={6}>
                  <strong>Platform Fee:</strong> {formatCurrency(task.platformFee)}
                </Col>
              </Row>

              {task.attachments && task.attachments.length > 0 && (
                <div className="mb-4">
                  <h5>Attachments</h5>
                  <ul>
                    {task.attachments.map((file, index) => (
                      <li key={index}>
                        <a href={file.url} target="_blank" rel="noopener noreferrer">
                          {file.name}
                        </a>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </Card.Body>
          </Card>

          {/* Proposals Tab */}
          <Tabs defaultActiveKey="details" className="mb-4">
            <Tab eventKey="details" title="Task Details">
              {/* Task details content */}
            </Tab>
            <Tab eventKey="proposals" title={`Proposals (${task.proposals.length})`}>
              {task.proposals.map((prop) => (
                <Card key={prop._id} className="mb-3">
                  <Card.Body>
                    <div className="d-flex justify-content-between">
                      <div>
                        <h6>{prop.freelancer.firstName} {prop.freelancer.lastName}</h6>
                        <p className="text-muted mb-1">
                          Rating: {prop.freelancer.averageRating} • 
                          Completed Tasks: {prop.freelancer.completedTasksCount}
                        </p>
                        <p>{prop.coverLetter}</p>
                      </div>
                      <div className="text-end">
                        <strong>{formatCurrency(prop.bidAmount)}</strong>
                        <div className="text-muted">{prop.estimatedDays} days</div>
                        <div className={`badge bg-${
                          prop.status === 'accepted' ? 'success' : 
                          prop.status === 'rejected' ? 'danger' : 'warning'
                        }`}>
                          {prop.status}
                        </div>
                      </div>
                    </div>
                  </Card.Body>
                </Card>
              ))}
            </Tab>
          </Tabs>
        </Col>

        <Col lg={4}>
          {/* Proposal Form for Freelancers */}
          {isAuthenticated && user.role === 'freelancer' && task.status === 'open' && (
            <Card>
              <Card.Body>
                <h5>Submit Proposal</h5>
                {hasSubmittedProposal ? (
                  <Alert variant="info">
                    You have already submitted a proposal for this task.
                  </Alert>
                ) : (
                  <Form onSubmit={submitProposal}>
                    <Form.Group className="mb-3">
                      <Form.Label>Bid Amount (USD)</Form.Label>
                      <Form.Control
                        type="number"
                        name="bidAmount"
                        value={proposal.bidAmount}
                        onChange={handleProposalChange}
                        required
                        min="0.01"
                        step="0.01"
                      />
                    </Form.Group>
                    <Form.Group className="mb-3">
                      <Form.Label>Estimated Days</Form.Label>
                      <Form.Control
                        type="number"
                        name="estimatedDays"
                        value={proposal.estimatedDays}
                        onChange={handleProposalChange}
                        required
                        min="1"
                      />
                    </Form.Group>
                    <Form.Group className="mb-3">
                      <Form.Label>Cover Letter</Form.Label>
                      <Form.Control
                        as="textarea"
                        rows={4}
                        name="coverLetter"
                        value={proposal.coverLetter}
                        onChange={handleProposalChange}
                        required
                      />
                    </Form.Group>
                    <Button
                      type="submit"
                      variant="primary"
                      className="w-100"
                      disabled={submitting}
                    >
                      {submitting ? 'Submitting...' : 'Submit Proposal'}
                    </Button>
                  </Form>
                )}
              </Card.Body>
            </Card>
          )}

          {/* Client Actions */}
          {isAuthenticated && user.role === 'client' && task.client._id === user._id && (
            <Card>
              <Card.Body>
                <h5>Task Actions</h5>
                <div className="d-grid gap-2">
                  <Button variant="outline-primary">Edit Task</Button>
                  <Button variant="outline-danger">Cancel Task</Button>
                </div>
              </Card.Body>
            </Card>
          )}
        </Col>
      </Row>
    </Container>
  );
};

export default TaskDetail;