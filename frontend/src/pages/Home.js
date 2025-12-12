import React from 'react';
import { Container, Row, Col, Button, Card } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

const Home = () => {
  const { isAuthenticated, user } = useAuth();

  return (
    <Container>
      {/* Hero Section */}
      <Row className="py-5 text-center">
        <Col>
          <h1 className="display-4 fw-bold mb-4">
            Welcome to DO4ME
          </h1>
          <p className="lead mb-4">
            Find talented freelancers for your tasks or offer your skills and get paid.
            Join thousands of clients and freelancers worldwide.
          </p>
          {!isAuthenticated ? (
            <div>
              <Link to="/register">
                <Button variant="primary" size="lg" className="me-3">
                  Get Started
                </Button>
              </Link>
              <Link to="/login">
                <Button variant="outline-primary" size="lg">
                  Sign In
                </Button>
              </Link>
            </div>
          ) : (
            <div>
              <h3>Welcome back, {user.firstName}!</h3>
              <Link to="/tasks">
                <Button variant="primary" size="lg" className="me-3">
                  Browse Tasks
                </Button>
              </Link>
              {user.role === 'client' && (
                <Link to="/create-task">
                  <Button variant="success" size="lg">
                    Post a Task
                  </Button>
                </Link>
              )}
            </div>
          )}
        </Col>
      </Row>

      {/* Features Section */}
      <Row className="py-5">
        <Col md={4} className="mb-4">
          <Card className="h-100 text-center">
            <Card.Body>
              <div className="feature-icon mb-3">💼</div>
              <Card.Title>For Clients</Card.Title>
              <Card.Text>
                Post your tasks and find qualified freelancers to get your work done quickly and efficiently.
              </Card.Text>
            </Card.Body>
          </Card>
        </Col>
        <Col md={4} className="mb-4">
          <Card className="h-100 text-center">
            <Card.Body>
              <div className="feature-icon mb-3">🚀</div>
              <Card.Title>For Freelancers</Card.Title>
              <Card.Text>
                Find work that matches your skills and build your portfolio while earning money.
              </Card.Text>
            </Card.Body>
          </Card>
        </Col>
        <Col md={4} className="mb-4">
          <Card className="h-100 text-center">
            <Card.Body>
              <div className="feature-icon mb-3">🔒</div>
              <Card.Title>Secure Payments</Card.Title>
              <Card.Text>
                Our secure payment system ensures you get paid for your work and clients get quality results.
              </Card.Text>
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </Container>
  );
};

export default Home;