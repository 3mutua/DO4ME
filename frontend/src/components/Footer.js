import React from 'react';
import { Container, Row, Col } from 'react-bootstrap';

const Footer = () => {
  return (
    <footer className="bg-dark text-light mt-5 py-3">
      <Container>
        <Row>
          <Col md={6}>
            <p>&copy; 2025 DO4ME. All rights reserved.</p>
          </Col>
          <Col md={6} className="text-end">
            <p>Connecting clients and freelancers worldwide</p>
          </Col>
        </Row>
      </Container>
    </footer>
  );
};

export default Footer;