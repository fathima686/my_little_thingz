import React from 'react';
import { useNavigate } from 'react-router-dom';
import ProfileModal from '../components/customer/ProfileModal';

export default function SupplierProfile() {
  const navigate = useNavigate();
  const handleClose = () => {
    navigate('/supplier', { replace: true });
  };

  return (
    <div style={{ minHeight: '100vh', background: '#f6f7fb', padding: '24px' }}>
      <div style={{ maxWidth: 1100, margin: '0 auto' }}>
        <ProfileModal onClose={handleClose} />
      </div>
    </div>
  );
}