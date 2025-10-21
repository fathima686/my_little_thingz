import React, { useEffect, useState } from 'react';
import { LuGift, LuChevronRight } from 'react-icons/lu';
import '../../styles/addon-suggestions.css';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

/**
 * Add-on Suggestions Component
 * 
 * Displays suggested add-ons based on cart total using Decision Tree logic.
 * Allows users to add suggested items before checkout.
 */
export default function AddonSuggestions({ 
  cartTotal, 
  auth, 
  onAddonSelected,
  cartItems 
}) {
  const [suggestions, setSuggestions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedAddons, setSelectedAddons] = useState(new Set());
  const [appliedRule, setAppliedRule] = useState(null);
  const [reasoning, setReasoning] = useState('');
  const [expanded, setExpanded] = useState(true);

  useEffect(() => {
    if (!auth?.user_id || cartItems?.length === 0) {
      setLoading(false);
      return;
    }

    fetchAddonSuggestions();
  }, [cartTotal, auth?.user_id, cartItems?.length]);

  const fetchAddonSuggestions = async () => {
    setLoading(true);
    try {
      const res = await fetch(`${API_BASE}/customer/addon-suggestion.php`, {
        headers: {
          'X-User-ID': auth?.user_id,
          'Authorization': `Bearer ${auth?.token}`
        }
      });
      const data = await res.json();
      
      if (data.status === 'success' && data.suggested_addons?.length > 0) {
        setSuggestions(data.suggested_addons);
        setAppliedRule(data.applied_rule);
        setReasoning(data.reasoning);
      } else {
        setSuggestions([]);
      }
    } catch (e) {
      console.error('Failed to fetch add-on suggestions:', e);
      setSuggestions([]);
    } finally {
      setLoading(false);
    }
  };

  const handleAddonToggle = (addonId) => {
    const newSelected = new Set(selectedAddons);
    
    if (newSelected.has(addonId)) {
      newSelected.delete(addonId);
    } else {
      newSelected.add(addonId);
    }
    
    setSelectedAddons(newSelected);
    
    // Convert selected IDs to addon objects for parent component
    const selectedAddonObjects = suggestions.filter(addon => newSelected.has(addon.id));
    
    // Notify parent component of selection
    if (onAddonSelected) {
      onAddonSelected(selectedAddonObjects, newSelected.size);
    }
  };

  // Expose selected addons for parent to access
  React.useImperativeHandle = React.useImperativeHandle || (() => {});
  const getSelectedAddons = () => {
    return suggestions.filter(addon => selectedAddons.has(addon.id));
  };

  if (loading || suggestions.length === 0) {
    return null;
  }

  const totalAddonPrice = suggestions
    .filter(addon => selectedAddons.has(addon.id))
    .reduce((sum, addon) => sum + addon.price, 0);

  return (
    <div className="addon-suggestions">
      <div className="addon-header" onClick={() => setExpanded(!expanded)}>
        <div className="addon-title-row">
          <LuGift className="addon-icon" />
          <div className="addon-title-text">
            <h3>Enhance Your Gift</h3>
            <p className="addon-rule">{appliedRule}</p>
          </div>
        </div>
        <LuChevronRight 
          className={`chevron ${expanded ? 'expanded' : ''}`}
          size={20}
        />
      </div>

      {expanded && (
        <div className="addon-content">
          {reasoning && (
            <p className="addon-reasoning">{reasoning}</p>
          )}

          <div className="addon-list">
            {suggestions.map(addon => (
              <div
                key={addon.id}
                className={`addon-item ${selectedAddons.has(addon.id) ? 'selected' : ''}`}
              >
                <input
                  type="checkbox"
                  id={`addon-${addon.id}`}
                  checked={selectedAddons.has(addon.id)}
                  onChange={() => handleAddonToggle(addon.id)}
                  className="addon-checkbox"
                />
                <label htmlFor={`addon-${addon.id}`} className="addon-label">
                  <div className="addon-info">
                    <div className="addon-header-small">
                      <span className="addon-icon-emoji">{addon.icon}</span>
                      <span className="addon-name">{addon.name}</span>
                    </div>
                    <p className="addon-description">{addon.description}</p>
                  </div>
                  <div className="addon-price">₹{addon.price}</div>
                </label>
              </div>
            ))}
          </div>

          {selectedAddons.size > 0 && (
            <div className="addon-summary">
              <p className="addon-summary-text">
                {selectedAddons.size} item{selectedAddons.size !== 1 ? 's' : ''} selected
              </p>
              <p className="addon-summary-price">
                +₹{totalAddonPrice} to your order
              </p>
            </div>
          )}
        </div>
      )}
    </div>
  );
}