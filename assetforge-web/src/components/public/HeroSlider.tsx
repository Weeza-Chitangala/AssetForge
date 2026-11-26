import React, { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight, ChevronLeft, ChevronRight, LayoutDashboard, ShieldCheck } from 'lucide-react';
import { useAuth } from '../../context/useAuth';
import infrastructureImg from '../../assets/landing/assetforge-hero-infrastructure.webp';
import assetTrackingImg from '../../assets/landing/assetforge-hero-asset-tracking.webp';
import itTeamImg from '../../assets/landing/assetforge-hero-it-team.webp';

interface HeroSlide {
  id: string;
  eyebrow: string;
  headline: string;
  description: string;
  image: string;
  alt: string;
  highlight: string;
}

const slides: HeroSlide[] = [
  {
    id: 'infrastructure',
    eyebrow: 'Enterprise IT Infrastructure',
    headline: 'Take Control of Your IT Assets.',
    description:
      "Manage, monitor and optimise your organisation's technology assets from one secure platform.",
    image: infrastructureImg,
    alt: 'Enterprise IT administrator inspecting structured server racks and network cabling with a tablet inside a modern data centre',
    highlight: 'Multi-tenant isolation • Role-based governance',
  },
  {
    id: 'asset-tracking',
    eyebrow: 'Asset Lifecycle & Custody',
    headline: 'Complete Visibility Across the Asset Lifecycle.',
    description:
      'Track equipment from registration and assignment through maintenance, repair and retirement.',
    image: assetTrackingImg,
    alt: 'IT technician scanning and inspecting enterprise laptop hardware inventory with a handheld barcode scanner',
    highlight: 'Checkout, check-in & transfer custody controls',
  },
  {
    id: 'it-team',
    eyebrow: 'Cross-Team Operational Clarity',
    headline: 'Enterprise Asset Management. Simplified.',
    description:
      'Bring your teams, assets and operational insights together with a secure, scalable ITAM solution.',
    image: itTeamImg,
    alt: 'Diverse enterprise IT operations team collaborating around laptops and an operations analytics screen in a modern glass office',
    highlight: 'Warranties • Repair workflows • Executive reporting',
  },
];

export const HeroSlider: React.FC = () => {
  const { isAuthenticated } = useAuth();
  const [activeIndex, setActiveIndex] = useState(0);
  const [isPaused, setIsPaused] = useState(false);
  const [prefersReducedMotion, setPrefersReducedMotion] = useState<boolean>(() => {
    if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
      return false;
    }
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  });

  useEffect(() => {
    if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
      return;
    }
    const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    const handleChange = () => setPrefersReducedMotion(mediaQuery.matches);
    mediaQuery.addEventListener('change', handleChange);
    return () => mediaQuery.removeEventListener('change', handleChange);
  }, []);

  const goToPrevious = useCallback(() => {
    setActiveIndex((prev) => (prev === 0 ? slides.length - 1 : prev - 1));
  }, []);

  const goToNext = useCallback(() => {
    setActiveIndex((prev) => (prev === slides.length - 1 ? 0 : prev + 1));
  }, []);

  useEffect(() => {
    if (isPaused || prefersReducedMotion) {
      return;
    }

    const timer = window.setInterval(() => {
      goToNext();
    }, 6500);

    return () => window.clearInterval(timer);
  }, [isPaused, prefersReducedMotion, goToNext]);

  const handleKeyDown = (e: React.KeyboardEvent<HTMLElement>) => {
    if (e.key === 'ArrowRight') {
      e.preventDefault();
      goToNext();
    } else if (e.key === 'ArrowLeft') {
      e.preventDefault();
      goToPrevious();
    }
  };

  const currentSlide = slides[activeIndex];

  return (
    <section
      role="region"
      aria-roledescription="carousel"
      aria-label="AssetForge platform highlights"
      tabIndex={0}
      onKeyDown={handleKeyDown}
      onMouseEnter={() => setIsPaused(true)}
      onMouseLeave={() => setIsPaused(false)}
      onFocusCapture={() => setIsPaused(true)}
      onBlurCapture={() => setIsPaused(false)}
      className="relative overflow-hidden bg-slate-950 text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-400"
    >
      <div className="relative min-h-[520px] sm:min-h-[560px] lg:min-h-[620px]">
        {slides.map((slide, index) => {
          const isActive = index === activeIndex;
          return (
            <div
              key={slide.id}
              aria-hidden={!isActive}
              className={`absolute inset-0 ${
                prefersReducedMotion ? '' : 'transition-opacity duration-700 ease-in-out'
              } ${isActive ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'}`}
            >
              <img
                src={slide.image}
                alt={slide.alt}
                width={1600}
                height={900}
                loading={index === 0 ? 'eager' : 'lazy'}
                decoding={index === 0 ? 'sync' : 'async'}
                className="h-full w-full object-cover object-center"
              />
              <div
                className="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-950/80 to-slate-950/35"
                aria-hidden="true"
              />
              <div
                className="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-slate-950/30"
                aria-hidden="true"
              />
            </div>
          );
        })}

        <div className="relative z-20 mx-auto flex min-h-[520px] sm:min-h-[560px] lg:min-h-[620px] max-w-7xl flex-col justify-center px-4 py-16 sm:px-6 lg:px-8">
          <div className="max-w-2xl">
            <div className="inline-flex items-center gap-2 rounded-full border border-indigo-400/30 bg-indigo-950/75 px-3.5 py-1 text-xs font-medium text-indigo-200 backdrop-blur-sm">
              <ShieldCheck className="h-3.5 w-3.5 text-indigo-400" aria-hidden="true" />
              <span>{currentSlide.eyebrow}</span>
            </div>

            <h1 className="mt-5 text-3xl font-bold tracking-tight text-white sm:text-5xl lg:text-[3.25rem] lg:leading-[1.12]">
              {currentSlide.headline}
            </h1>

            <p className="mt-5 max-w-xl text-base leading-relaxed text-slate-200 sm:text-lg">
              {currentSlide.description}
            </p>

            <p className="mt-4 text-xs font-medium uppercase tracking-wider text-indigo-300">
              {currentSlide.highlight}
            </p>

            <div className="mt-8 flex flex-wrap items-center gap-3.5">
              {isAuthenticated ? (
                <>
                  <Link
                    to="/dashboard"
                    className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-950/40 hover:bg-indigo-500 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 focus:ring-offset-slate-950"
                  >
                    <LayoutDashboard className="h-4 w-4" aria-hidden="true" />
                    Go to Dashboard
                  </Link>
                  <a
                    href="#capabilities"
                    className="inline-flex items-center gap-2 rounded-lg border border-slate-500/70 bg-slate-900/70 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800/90 transition-colors backdrop-blur-sm"
                  >
                    Explore Capabilities
                    <ArrowRight className="h-4 w-4" aria-hidden="true" />
                  </a>
                </>
              ) : (
                <>
                  <Link
                    to="/login"
                    className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-950/40 hover:bg-indigo-500 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 focus:ring-offset-slate-950"
                  >
                    Sign In to Workspace
                    <ArrowRight className="h-4 w-4" aria-hidden="true" />
                  </Link>
                  <Link
                    to="/register"
                    className="inline-flex items-center gap-2 rounded-lg border border-slate-400/50 bg-slate-900/70 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800/90 transition-colors backdrop-blur-sm"
                  >
                    Request Organisation Access
                  </Link>
                </>
              )}
            </div>
          </div>

          <div className="mt-10 flex items-center justify-between gap-4 border-t border-white/10 pt-6">
            <div className="flex items-center gap-2" role="tablist" aria-label="Slide selector">
              {slides.map((slide, index) => {
                const isSelected = index === activeIndex;
                return (
                  <button
                    key={slide.id}
                    type="button"
                    role="tab"
                    aria-selected={isSelected}
                    aria-label={`Go to slide ${index + 1}: ${slide.headline}`}
                    onClick={() => setActiveIndex(index)}
                    className={`h-2.5 rounded-full transition-all focus:outline-none focus:ring-2 focus:ring-indigo-400 ${
                      isSelected ? 'w-8 bg-indigo-400' : 'w-2.5 bg-white/40 hover:bg-white/70'
                    }`}
                  />
                );
              })}
              <span className="ml-3 text-xs font-medium text-slate-300" aria-live="polite">
                {activeIndex + 1} / {slides.length}
              </span>
            </div>

            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={goToPrevious}
                aria-label="Previous slide"
                className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-white/20 bg-slate-900/70 text-white hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-400"
              >
                <ChevronLeft className="h-5 w-5" aria-hidden="true" />
              </button>
              <button
                type="button"
                onClick={goToNext}
                aria-label="Next slide"
                className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-white/20 bg-slate-900/70 text-white hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-400"
              >
                <ChevronRight className="h-5 w-5" aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};
