// Quick test to verify all icons exist
import React from 'react';

// Test all icons used in the subscription system - ONLY VERIFIED WORKING ICONS
import { 
  LuX, LuCheck, LuCrown, LuStar, LuZap, LuVideo, LuUpload, LuAward, LuUsers, LuClock,
  LuTrendingUp, LuTarget,
  LuImage, LuPlay, LuLock, LuArrowLeft, LuLogOut, LuHeart, LuBell, LuUser
} from 'react-icons/lu';

const IconTest = () => {
  const icons = [
    { name: 'LuX', component: LuX },
    { name: 'LuCheck', component: LuCheck },
    { name: 'LuCrown', component: LuCrown },
    { name: 'LuStar', component: LuStar },
    { name: 'LuZap', component: LuZap },
    { name: 'LuVideo', component: LuVideo },
    { name: 'LuUpload', component: LuUpload },
    { name: 'LuAward', component: LuAward },
    { name: 'LuUsers', component: LuUsers },
    { name: 'LuClock', component: LuClock },
    { name: 'LuTrendingUp', component: LuTrendingUp },
    { name: 'LuTarget', component: LuTarget },
    { name: 'LuImage', component: LuImage },
    { name: 'LuPlay', component: LuPlay },
    { name: 'LuLock', component: LuLock },
    { name: 'LuHeart', component: LuHeart }
  ];

  return (
    <div style={{ padding: '20px' }}>
      <h2>Icon Test - All icons should display</h2>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(5, 1fr)', gap: '20px' }}>
        {icons.map(({ name, component: Icon }) => (
          <div key={name} style={{ textAlign: 'center', padding: '10px', border: '1px solid #ccc' }}>
            <Icon size={24} />
            <div style={{ fontSize: '12px', marginTop: '5px' }}>{name}</div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default IconTest;