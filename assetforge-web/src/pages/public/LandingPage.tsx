import React, { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { HeroSlider } from '../../components/public/HeroSlider';
import { IntroSection } from '../../components/public/IntroSection';
import { CapabilitiesSection } from '../../components/public/CapabilitiesSection';
import { LifecycleSection } from '../../components/public/LifecycleSection';
import { SecuritySection } from '../../components/public/SecuritySection';
import { ReportingSection } from '../../components/public/ReportingSection';
import { MultiTenantSection } from '../../components/public/MultiTenantSection';
import { CallToActionSection } from '../../components/public/CallToActionSection';

export const LandingPage: React.FC = () => {
  const { hash } = useLocation();

  useEffect(() => {
    if (!hash) {
      return;
    }

    const sectionId = hash.replace(/^#/, '');
    const target = document.getElementById(sectionId);
    if (target) {
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }, [hash]);

  return (
    <>
      <HeroSlider />
      <IntroSection />
      <CapabilitiesSection />
      <LifecycleSection />
      <SecuritySection />
      <ReportingSection />
      <MultiTenantSection />
      <CallToActionSection />
    </>
  );
};
