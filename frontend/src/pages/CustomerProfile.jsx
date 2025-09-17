import React from 'react';
import { useNavigate } from 'react-router-dom';
import ProfileModal from '../components/customer/ProfileModal';

// Standalone page that renders only the profile interface on a clean page.
export default function CustomerProfile() {
  const navigate = useNavigate();
  const handleClose = () => {
    // Always go to dashboard to avoid back-blocking from ProtectedRoute
    navigate('/dashboard', { replace: true });
  };

  return (
    <div style={{ minHeight: '100vh', background: '#f6f7fb', padding: '24px' }}>
      <div style={{ maxWidth: 1100, margin: '0 auto' }}>
        <ProfileModal onClose={handleClose} />
      </div>
    </div>
  );
}