import React from "react";
import { BrowserRouter as Router, Routes, Route } from "react-router-dom";
import { AuthProvider } from "./contexts/AuthContext";
import { ToastProvider } from "./contexts/ToastContext";
import SessionGuard from "./components/SessionGuard";
import ProtectedRoute from "./components/ProtectedRoute";
import Index from "./pages/Index";
import Login from "./pages/Login";
import Register from "./pages/Register";
import Auth from "./pages/Auth";
import CustomerDashboard from "./pages/CustomerDashboard";
import CustomerProfile from "./pages/CustomerProfile";
import AdminDashboard from "./pages/AdminDashboard";
import ForgotPassword from "./pages/ForgotPassword";
import ResetPassword from "./pages/ResetPassword";
import SupplierDashboard from "./pages/SupplierDashboard";
import SupplierProductNew from "./pages/SupplierProductNew";
import SupplierRequirements from "./pages/SupplierRequirements";
import SupplierProfile from "./pages/SupplierProfile";
import CartPage from "./pages/CartPage";
import AdminRequirements from "./pages/AdminRequirements";


function App() {
  return (
    <Router>
      <AuthProvider>
        <ToastProvider>
          <SessionGuard>
            <Routes>
              <Route path="/" element={<Index />} />
              <Route path="/login" element={<Login />} />
              <Route path="/register" element={<Register />} />
              <Route path="/auth" element={<Auth />} />
              <Route path="/forgot" element={<ForgotPassword />} />
              <Route path="/reset" element={<ResetPassword />} />
              
              {/* Protected Routes */}
              <Route 
                path="/dashboard" 
                element={
                  <ProtectedRoute requiredRoles={['customer']}>
                    <CustomerDashboard />
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/profile" 
                element={
                  <ProtectedRoute requiredRoles={['customer']}>
                    <CustomerProfile />
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/cart" 
                element={
                  <ProtectedRoute requiredRoles={['customer']}>
                    <CartPage />
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/supplier" 
                element={
                  <ProtectedRoute requiredRoles={['supplier']}>
                    <SupplierDashboard />
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/supplier/profile" 
                element={
                  <ProtectedRoute requiredRoles={['supplier']}>
                    <SupplierProfile />
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/supplier/products/new" 
                element={
                  <ProtectedRoute requiredRoles={['supplier']}>
                    <SupplierProductNew />
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/supplier/requirements" 
                element={
                  <ProtectedRoute requiredRoles={['supplier']}>
                    <SupplierRequirements />
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/admin" 
                element={
                  <ProtectedRoute requiredRoles={['admin']}>
                    <AdminDashboard />
                  </ProtectedRoute>
                } 
              />
              <Route 
                path="/admin/requirements" 
                element={
                  <ProtectedRoute requiredRoles={['admin']}>
                    <AdminRequirements />
                  </ProtectedRoute>
                } 
              />
            </Routes>
          </SessionGuard>
        </ToastProvider>
      </AuthProvider>
    </Router>
  );
}

export default App;
