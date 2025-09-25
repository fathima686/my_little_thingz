import React, { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { motion } from "framer-motion";
import AOS from "aos";
import "aos/dist/aos.css";
import "../styles/index.css";
import logo from "../assets/logo.png";
import { LuGift, LuCamera, LuFileHeart, LuFlower, LuPhone, LuMail, LuMapPin } from "react-icons/lu";

// Auto-load images from src/assets (excludes logo/react)
const assetModules = import.meta.glob("../assets/*.{png,jpg,jpeg,webp}", { eager: true });
const allAssets = Object.entries(assetModules)
  .map(([path, mod]) => ({
    src: mod?.default,
    name: path.split("/").pop()?.toLowerCase() || "",
  }))
  .filter(Boolean)
  // ignore non-product images
  .filter((a) => a.name && !a.name.includes("logo") && !a.name.includes("react"));

const uploadedImages = allAssets.map((a) => a.src);

// helpers to map a specific category image by filename hints
const pickBy = (hints) => {
  const arr = Array.isArray(hints) ? hints : [hints];
  const entry = allAssets.find((a) => arr.some((h) => a.name.includes(h)));
  return entry?.src;
};

const picks = {
  giftBoxSet: pickBy(["gift box set", "giftbox set", "gift-set", "boxset"]),
  giftBox: pickBy(["gift box", "giftbox", "gift"]),
  polaroid: pickBy(["poloroid", "polaroid", "poloroids"]),
  drawings: pickBy(["drawing", "drawings", "sketch"]),
  bouquets: pickBy(["boq", "bouquet", "boaq", "boaquet"]),
  wedding: pickBy(["wedding", "wedding card", "card"]),
  album: pickBy(["album", "photobook"]),
};

// Top promotional offers strip
const OffersStrip = () => {
  const [offers, setOffers] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let alive = true;
    const load = async () => {
      try {
        const res = await fetch("http://localhost/my_little_thingz/backend/api/customer/offers-promos.php");
        const data = await res.json();
        if (!alive) return;
        if (data?.status === "success" && Array.isArray(data.offers)) {
          setOffers(data.offers);
        }
      } catch (_) {}
      finally { if (alive) setLoading(false); }
    };
    load();
    return () => { alive = false; };
  }, []);

  if (loading) return null;
  if (!offers.length) return null;

  return (
    <section className="offers-strip" aria-label="Promotional Offers">
      <div className="container offers-scroller" data-aos="fade-up">
        {offers.map((o) => (
          <div key={o.id} className="offer-card">
            <img src={o.image_url} alt={o.title || "Promotional banner"} />
            {o.title ? <div className="offer-title">{o.title}</div> : null}
          </div>
        ))}
      </div>
    </section>
  );
};

const Header = () => (
  <header className="site-header">
    <div className="container header-inner">
      <div className="brand">
        <img src={logo} alt="My Little Thingz" className="brand-logo" />
        <span className="brand-name">My Little Thingz</span>
      </div>

      <nav className="main-nav">
        <a href="#home" className="nav-link active">Home</a>
        <a href="#gallery" className="nav-link">Gallery</a>
        <a href="#services" className="nav-link">Services</a>
        <a href="#about" className="nav-link">About</a>
        <a href="#contact" className="nav-link">Contact</a>
      </nav>

      <div className="auth-actions">
        <Link to="/login" className="btn btn-outline small">Login</Link>
        <Link to="/register" className="btn btn-primary small">Register</Link>
      </div>
    </div>
  </header>
);

const Hero = () => {
  const float = {
    animate: {
      y: [0, -12, 0],
      transition: { duration: 6, repeat: Infinity, ease: "easeInOut" },
    },
  };

  // Hero mosaic uses up to 4 top picks or fallbacks
  const mosaic = useMemo(() => {
    const candidates = [
      picks.giftBox || picks.giftBoxSet,
      picks.polaroid,
      picks.wedding,
      picks.bouquets || picks.album || picks.drawings,
    ].filter(Boolean);
    return candidates.length >= 4 ? candidates.slice(0, 4) : [...candidates, ...uploadedImages].slice(0, 4);
  }, []);

  return (
    <section id="home" className="hero">
      <div className="container hero-grid">
        <div className="hero-copy" data-aos="fade-right" data-aos-duration="800">
          <h1 className="headline">
            <span>MY LITTLE</span>
            <span>THINGZ</span>
          </h1>
          <p className="subhead">
            My Little Thingz – Where creativity meets personalization for every special moment.
          </p>
          <div className="cta-group">
            <a href="#gallery" className="btn btn-primary">
              View Our Work
              <span className="arrow">→</span>
            </a>
            <Link to="/register" className="btn btn-outline">Order Custom Gift</Link>
          </div>
        </div>

        <div className="hero-visual" data-aos="fade-left" data-aos-duration="900">
          <div className="mosaic">
            {[0, 1, 2, 3].map((i) => (
              <motion.div
                className={`mosaic-card m${i + 1}`}
                key={i}
                variants={float}
                animate="animate"
                whileHover={{ scale: 1.03, rotate: i % 2 === 0 ? -1 : 1 }}
                transition={{ type: "spring", stiffness: 120, damping: 12 }}
              >
                {mosaic[i] ? (
                  <img src={mosaic[i]} alt="Showcase" />
                ) : (
                  <div className="ph ph-hero shimmer" />
                )}
              </motion.div>
            ))}
          </div>
        </div>
      </div>

      {/* Decorative gradient blobs */}
      <div className="blob b1" />
      <div className="blob b2" />
    </section>
  );
};

// Pinterest-style Masonry Gallery using your uploaded images
const Gallery = () => {
  const items = useMemo(() => uploadedImages, []);

  return (
    <section id="gallery" className="section">
      <div className="container">
        <div className="section-head" data-aos="fade-up">
          <h2>Inspiration Wall</h2>
        </div>

        <div className="masonry" data-aos="fade-up" data-aos-delay="100">
          {items.length === 0 && (
            <div className="grid cards">
              {[...Array(6)].map((_, i) => (
                <article key={i} className="card"><div className="card-media"><div className="ph ph-card shimmer" /></div></article>
              ))}
            </div>
          )}

          {items.map((src, idx) => (
            <a key={idx} href={src} className="masonry-item" title="Open image" target="_blank" rel="noreferrer">
              <img src={src} alt={`Gallery item ${idx + 1}`} />
            </a>
          ))}
        </div>
      </div>
    </section>
  );
};

const Services = () => (
  <section id="services" className="section tint">
    <div className="container">
      <div className="section-head" data-aos="fade-up">
        <h2>What We Offer</h2>
        <p>Specialized in creating memorable gifts and keepsakes for your special moments</p>
      </div>

      <div className="grid features" data-aos="fade-up" data-aos-delay="100">
        {[{ icon: <LuGift />, title: "Custom Gift Boxes", text: "Personalized gift boxes tailored to your story" },
          { icon: <LuCamera />, title: "Photo Memories", text: "Transform photos into beautiful keepsakes" },
          { icon: <LuFileHeart />, title: "Wedding Packages", text: "Complete stationery & memory packages" },
          { icon: <LuFlower />, title: "Fresh Bouquets", text: "Beautiful arrangements for any occasion" },
        ].map((f, i) => (
          <div className="feature" key={i}>
            <div className="feature-icon">{f.icon}</div>
            <h3>{f.title}</h3>
            <p>{f.text}</p>
          </div>
        ))}
      </div>
    </div>
  </section>
);

const Contact = () => (
  <section id="contact" className="section">
    <div className="container">
      <div className="grid contact" data-aos="fade-up">
        <div className="contact-card">
          <div className="contact-icon"><LuPhone /></div>
          <h3>Call Us</h3>
          <p className="muted">91+7836338346</p>
        
        </div>
        <div className="contact-card">
          <div className="contact-icon"><LuMail /></div>
          <h3>Email Us</h3>
          <p className="muted">orders@mylittlethingz.com</p>
          <p className="tiny">Custom quotes within 4 hours</p>
        </div>
        <div className="contact-card">
          <div className="contact-icon"><LuMapPin /></div>
          <h3>Visit Studio</h3>
          <p className="muted">456 Memory Lane</p>
          <p className="tiny">Gift District, NY 10001</p>
        </div>
      </div>
    </div>
  </section>
);

const Footer = () => (
  <footer className="site-footer">
    <div className="container footer-grid">
      <div>
        <div className="brand foot">
          <img src={logo} alt="logo" className="brand-logo" />
          <span className="brand-name">My Little Thingz</span>
        </div>
        <p className="muted">Creating personalized gifts and preserving precious memories, one little thing at a time.</p>
      </div>
      <div>
        <h4>Our Services</h4>
        <ul className="list">
          <li>Custom Gift Boxes</li>
          <li>Photo Frames</li>
          <li>Wedding Cards</li>
          <li>Fresh Bouquets</li>
        </ul>
      </div>
      <div>
        <h4>Quick Links</h4>
        <ul className="list">
          <li><a href="#gallery">Gallery</a></li>
          <li><a href="#services">Services</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="#contact">Contact</a></li>
        </ul>
      </div>
      <div>
        <h4>Follow Us</h4>
        <ul className="list">
          <li>Instagram @mylittlethingz</li>
          <li>Pinterest</li>
          
        </ul>
      </div>
    </div>
    <div className="footnote">© {new Date().getFullYear()} My Little Thingz. All rights reserved.</div>
  </footer>
);

export default function Index() {
  useEffect(() => {
    AOS.init({ once: true, offset: 60, duration: 700, easing: "ease-out" });
  }, []);

  return (
    <main>
      <Header />
      {/* OffersStrip removed */}
      <Hero />

      <section id="about" className="section narrow" data-aos="fade-up">
        <div className="container">
          <h2 className="center">About Us</h2>
          <p className="muted center">We’re a creative studio dedicated to crafting personalized keepsakes that make people smile. Whether you’re celebrating a birthday, wedding, or any milestone, we’ll help you tell your story with thoughtful design and delicate details.</p>
        </div>
      </section>

      <Gallery />
      <Services />
      <Contact />
      <Footer />
    </main>
  );
}