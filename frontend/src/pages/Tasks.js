import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Button, Form, Spinner } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import axios from 'axios';

const Tasks = () => {
  const [tasks, setTasks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    category: '',
    minBudget: '',
    maxBudget: '',
    urgency: '',
    search: ''
  });

  useEffect(() => {
    fetchTasks();
  }, [filters]);

  const fetchTasks = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams();
      
      Object.entries(filters).forEach(([key, value]) => {
        if (value) params.append(key, value);
      });

      const res = await axios.get(`/api/tasks?${params}`);
      setTasks(res.data.data.tasks);
    } catch (error) {
      console.error('Error fetching tasks:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({
      ...prev,
      [key]: value
    }));
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

  return (
    <Container>
      <Row>
        <Col md={3}>
          {/* Filters */}
          <Card className="mb-4">
            <Card.Header>
              <h5 className="mb-0">Filters</h5>
            </Card.Header>
            <Card.Body>
              <Form>
                <Form.Group className="mb-3">
                  <Form.Label>Search</Form.Label>
                  <Form.Control
                    type="text"
                    placeholder="Search tasks..."
                    value={filters.search}
                    onChange={(e) => handleFilterChange('search', e.target.value)}
                  />
                </Form.Group>

                <Form.Group className="mb-3">
                  <Form.Label>Category</Form.Label>
                  <Form.Select
                    value={filters.category}
                    onChange={(e) => handleFilterChange('category', e.target.value)}
                  >
                    <option value="">All Categories</option>
                    <option value="writing">Writing</option>
                    <option value="design">Design</option>
                    <option value="programming">Programming</option>
                    <option value="marketing">Marketing</option>
                    <option value="data_entry">Data Entry</option>
                    <option value="customer_service">Customer Service</option>
                    <option value="other">Other</option>
                  </Form.Select>
                </Form.Group>

                <Form.Group className="mb-3">
                  <Form.Label>Budget Range</Form.Label>
                  <Row>
                    <Col>
                      <Form.Control
                        type="number"
                        placeholder="Min"
                        value={filters.minBudget}
                        onChange={(e) => handleFilterChange('minBudget', e.target.value)}
                      />
                    </Col>
                    <Col>
                      <Form.Control
                        type="number"
                        placeholder="Max"
                        value={filters.maxBudget}
                        onChange={(e) => handleFilterChange('maxBudget', e.target.value)}
                      />
                    </Col>
                  </Row>
                </Form.Group>

                <Form.Group className="mb-3">
                  <Form.Label>Urgency</Form.Label>
                  <Form.Select
                    value={filters.urgency}
                    onChange={(e) => handleFilterChange('urgency', e.target.value)}
                  >
                    <option value="">All</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                  </Form.Select>
                </Form.Group>
              </Form>
            </Card.Body>
          </Card>
        </Col>

        <Col md={9}>
          {/* Tasks List */}
          <Row>
            {tasks.length === 0 ? (
              <Col>
                <Card className="text-center py-5">
                  <Card.Body>
                    <h5>No tasks found</h5>
                    <p className="text-muted">
                      {Object.values(filters).some(f => f) 
                        ? 'Try adjusting your filters' 
                        : 'Be the first to post a task!'}
                    </p>
                  </Card.Body>
                </Card>
              </Col>
            ) : (
              tasks.map(task => (
                <Col key={task._id} lg={6} className="mb-4">
                  <Card className="h-100">
                    <Card.Body>
                      <div className="d-flex justify-content-between align-items-start mb-2">
                        <span className={`badge bg-${
                          task.urgency === 'high' ? 'danger' : 
                          task.urgency === 'medium' ? 'warning' : 'success'
                        }`}>
                          {task.urgency}
                        </span>
                        <span className="badge bg-secondary">
                          {task.category}
                        </span>
                      </div>
                      
                      <Card.Title className="h5">
                        <Link to={`/tasks/${task._id}`} className="text-decoration-none">
                          {task.title}
                        </Link>
                      </Card.Title>
                      
                      <Card.Text className="text-muted small">
                        {task.description.substring(0, 150)}...
                      </Card.Text>
                      
                      <div className="d-flex justify-content-between align-items-center mt-auto">
                        <div>
                          <strong>{formatCurrency(task.budget)}</strong>
                          <div className="text-muted small">
                            by {task.client.firstName} {task.client.lastName}
                          </div>
                        </div>
                        <Button 
                          as={Link} 
                          to={`/tasks/${task._id}`}
                          variant="outline-primary" 
                          size="sm"
                        >
                          View Details
                        </Button>
                      </div>
                    </Card.Body>
                  </Card>
                </Col>
              ))
            )}
          </Row>
        </Col>
      </Row>
    </Container>
  );
};

export default Tasks;