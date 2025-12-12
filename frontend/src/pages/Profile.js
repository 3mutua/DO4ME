import React, { useState } from 'react';
import { Form, Button, Card, Container, Row, Col, Alert, Spinner, Tab, Tabs } from 'react-bootstrap';
import { useAuth } from '../contexts/AuthContext';

const Profile = () => {
  const { user, updateProfile } = useAuth();
  const [formData, setFormData] = useState({
    firstName: user?.firstName || '',
    lastName: user?.lastName || '',
    phone: user?.phone || '',
    country: user?.country || '',
    bio: user?.bio || '',
    skills: user?.skills?.join(', ') || ''
  });
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');
    setLoading(true);

    const profileData = {
      ...formData,
      skills: formData.skills.split(',').map(skill => skill.trim()).filter(skill => skill)
    };

    const result = await updateProfile(profileData);
    if (result.success) {
      setSuccess('Profile updated successfully!');
    } else {
      setError(result.message);
    }
    setLoading(false);
  };

  if (!user) {
    return (
      <Container>
        <Alert variant="warning">Please log in to view your profile.</Alert>
      </Container>
    );
  }

  return (
    <Container>
      <Row>
        <Col lg={8}>
          <Card>
            <Card.Body>
              <h2 className="mb-4">Profile Settings</h2>
              {error && <Alert variant="danger">{error}</Alert>}
              {success && <Alert variant="success">{success}</Alert>}
              
              <Tabs defaultActiveKey="profile" className="mb-3">
                <Tab eventKey="profile" title="Profile">
                  <Form onSubmit={handleSubmit}>
                    <Row>
                      <Col md={6}>
                        <Form.Group className="mb-3">
                          <Form.Label>First Name</Form.Label>
                          <Form.Control
                            type="text"
                            name="firstName"
                            value={formData.firstName}
                            onChange={handleChange}
                            required
                          />
                        </Form.Group>
                      </Col>
                      <Col md={6}>
                        <Form.Group className="mb-3">
                          <Form.Label>Last Name</Form.Label>
                          <Form.Control
                            type="text"
                            name="lastName"
                            value={formData.lastName}
                            onChange={handleChange}
                            required
                          />
                        </Form.Group>
                      </Col>
                    </Row>

                    <Form.Group className="mb-3">
                      <Form.Label>Email</Form.Label>
                      <Form.Control
                        type="email"
                        value={user.email}
                        disabled
                      />
                      <Form.Text className="text-muted">
                        Email cannot be changed
                      </Form.Text>
                    </Form.Group>

                    <Row>
                      <Col md={6}>
                        <Form.Group className="mb-3">
                          <Form.Label>Phone</Form.Label>
                          <Form.Control
                            type="text"
                            name="phone"
                            value={formData.phone}
                            onChange={handleChange}
                          />
                        </Form.Group>
                      </Col>
                      <Col md={6}>
                        <Form.Group className="mb-3">
                          <Form.Label>Country</Form.Label>
                          <Form.Control
                            type="text"
                            name="country"
                            value={formData.country}
                            onChange={handleChange}
                          />
                        </Form.Group>
                      </Col>
                    </Row>

                    <Form.Group className="mb-3">
                      <Form.Label>Bio</Form.Label>
                      <Form.Control
                        as="textarea"
                        rows={4}
                        name="bio"
                        value={formData.bio}
                        onChange={handleChange}
                        maxLength={2000}
                      />
                    </Form.Group>

                    <Form.Group className="mb-3">
                      <Form.Label>Skills (comma separated)</Form.Label>
                      <Form.Control
                        type="text"
                        name="skills"
                        value={formData.skills}
                        onChange={handleChange}
                        placeholder="e.g., JavaScript, React, Node.js"
                      />
                    </Form.Group>

                    <Button
                      variant="primary"
                      type="submit"
                      disabled={loading}
                    >
                      {loading ? <Spinner animation="border" size="sm" /> : 'Update Profile'}
                    </Button>
                  </Form>
                </Tab>

                <Tab eventKey="stats" title="Statistics">
                  <Row>
                    <Col md={6}>
                      <Card className="mb-3">
                        <Card.Body>
                          <h6>Wallet Balance</h6>
                          <h3>${user.walletBalance?.toFixed(2) || '0.00'}</h3>
                        </Card.Body>
                      </Card>
                    </Col>
                    <Col md={6}>
                      <Card className="mb-3">
                        <Card.Body>
                          <h6>Completed Tasks</h6>
                          <h3>{user.completedTasksCount || 0}</h3>
                        </Card.Body>
                      </Card>
                    </Col>
                  </Row>
                  {user.role === 'freelancer' && (
                    <Card>
                      <Card.Body>
                        <h6>Average Rating</h6>
                        <h3>{user.averageRating?.toFixed(1) || '0.0'}/5.0</h3>
                      </Card.Body>
                    </Card>
                  )}
                </Tab>
              </Tabs>
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </Container>
  );
};

export default Profile;