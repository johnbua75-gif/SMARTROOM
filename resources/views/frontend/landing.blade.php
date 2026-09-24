<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>SmartRoom – PSU Asingan Campus</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<style>
:root {
  --navy:       #0b1640;
  --navy-mid:   #112060;
  --navy-light: #1a2f80;
  --gold:       #f5c518;
  --gold-deep:  #d4a10a;
  --gold-pale:  #fde97a;
  --white:      #ffffff;
  --muted:      rgba(255,255,255,0.55);
  --yellow:     #f5c518;
}

/* ── RESET ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body { font-family: 'Poppins', Arial, Helvetica, sans-serif; background: var(--navy); color: var(--white); overflow-x: hidden; }

/* ── NOISE TEXTURE ── */
body::before {
  content: ''; position: fixed; inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
  pointer-events: none; z-index: 0; opacity: 0.35;
}

/* ── REVEAL ANIMATIONS ── */
.reveal { opacity: 0; transform: translateY(36px); transition: opacity 0.75s cubic-bezier(0.16,1,0.3,1), transform 0.75s cubic-bezier(0.16,1,0.3,1); }
.reveal.visible { opacity: 1; transform: translateY(0); }
.reveal-delay-1 { transition-delay: 0.08s; }
.reveal-delay-2 { transition-delay: 0.16s; }
.reveal-delay-3 { transition-delay: 0.24s; }
.reveal-delay-4 { transition-delay: 0.32s; }

/* ════════════════════════════════════
   NAV
════════════════════════════════════ */
nav {
  position: fixed; top: 0; left: 0; right: 0; z-index: 100;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 5vw; height: 72px;
  background: rgba(11,22,64,0.8);
  backdrop-filter: blur(24px) saturate(1.5);
  border-bottom: 1px solid rgba(245,197,24,0.1);
  transition: background 0.3s, box-shadow 0.3s;
}
nav.scrolled { background: rgba(11,22,64,0.97); box-shadow: 0 4px 32px rgba(0,0,0,0.45); border-bottom-color: rgba(245,197,24,0.18); }

/* LOGO */
.sidebar-logo { display: flex; align-items: center; gap: 12px; text-decoration: none; }
.logo-mark {
  width: 42px; height: 42px; background: var(--yellow); border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; box-shadow: 0 4px 14px rgba(245,197,24,0.45);
  transition: transform 0.3s cubic-bezier(0.16,1,0.3,1), box-shadow 0.3s;
}
.sidebar-logo:hover .logo-mark { transform: translateY(-2px) scale(1.07); box-shadow: 0 8px 24px rgba(245,197,24,0.6); }
.logo-text { line-height: 1; }
.logo-text .brand-psu { font-size: 0.58rem; font-weight: 700; letter-spacing: 0.2em; color: rgba(255,255,255,0.4); text-transform: uppercase; display: block; margin-bottom: 3px; }
.logo-text .brand-main { font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 1.05rem; font-weight: 700; color: #fff; letter-spacing: -0.01em; }
.logo-text .brand-main span { color: var(--yellow); }

/* NAV LINKS */
.nav-links { display: flex; gap: 32px; list-style: none; align-items: center; }
.nav-links a { color: var(--muted); text-decoration: none; font-size: 0.88rem; font-weight: 500; transition: color 0.2s; position: relative; display: flex; align-items: center; gap: 6px; }
.nav-links a .nav-icon-sm { opacity: 0.5; transition: opacity 0.2s; }
.nav-links a::after { content: ''; position: absolute; bottom: -4px; left: 0; width: 0; height: 2px; background: var(--gold); border-radius: 2px; transition: width 0.3s; }
.nav-links a:hover { color: var(--gold); }
.nav-links a:hover .nav-icon-sm { opacity: 1; }
.nav-links a:hover::after { width: 100%; }
.nav-cta {
  background: var(--gold) !important; color: var(--navy) !important; font-weight: 700 !important;
  padding: 10px 22px; border-radius: 10px; font-size: 0.86rem !important;
  box-shadow: 0 4px 15px rgba(245,197,24,0.3); transition: all 0.25s !important;
  display: flex !important; align-items: center; gap: 7px;
}
.nav-cta::after { display: none !important; }
.nav-cta:hover { background: var(--gold-pale) !important; transform: translateY(-2px) !important; box-shadow: 0 8px 25px rgba(245,197,24,0.45) !important; color: var(--navy) !important; }

/* HAMBURGER */
.nav-hamburger { display: none; flex-direction: column; gap: 5px; background: none; border: none; cursor: pointer; padding: 4px; }
.nav-hamburger span { display: block; width: 24px; height: 2px; background: var(--white); border-radius: 2px; transition: all 0.3s; }

/* ════════════════════════════════════
   HERO
════════════════════════════════════ */
.hero { position: relative; min-height: 100vh; display: flex; align-items: center; padding: 120px 5vw 80px; overflow: hidden; }
.hero-bg { position: absolute; inset: 0; z-index: 0; }
.hero-bg img { width: 100%; height: 100%; object-fit: cover; object-position: center 30%; display: block; filter: brightness(0.55) contrast(1.05) saturate(0.85); animation: slowZoom 28s ease-in-out infinite alternate; }
@keyframes slowZoom { 0% { transform: scale(1); } 100% { transform: scale(1.08); } }
.hero-bg::after {
  content: ''; position: absolute; inset: 0;
  background:
    linear-gradient(to right, rgba(11,22,64,0.92) 0%, rgba(11,22,64,0.8) 22%, rgba(11,22,64,0.48) 48%, rgba(17,32,96,0.12) 100%),
    linear-gradient(to bottom, rgba(11,22,64,0.55) 0%, transparent 18%, transparent 55%, rgba(11,22,64,0.72) 82%, rgba(11,22,64,0.9) 100%);
  pointer-events: none; z-index: 1;
}
.hero::after { content: ''; position: absolute; inset: 0; background-image: linear-gradient(rgba(245,197,24,0.025) 1px, transparent 1px), linear-gradient(90deg, rgba(245,197,24,0.025) 1px, transparent 1px); background-size: 60px 60px; mask-image: radial-gradient(ellipse 75% 75% at 50% 50%, black 0%, transparent 75%); pointer-events: none; z-index: 1; }

.orb { position: absolute; border-radius: 50%; filter: blur(80px); pointer-events: none; z-index: 1; }
.orb-1 { width: 600px; height: 600px; background: radial-gradient(circle, rgba(245,197,24,0.09) 0%, transparent 70%); top: -150px; right: 5%; animation: float 11s ease-in-out infinite; }
.orb-2 { width: 420px; height: 420px; background: radial-gradient(circle, rgba(26,47,128,0.45) 0%, transparent 70%); bottom: 5%; left: -80px; animation: float 13s ease-in-out infinite reverse; }
@keyframes float { 0%,100% { transform: translateY(0) translateX(0); } 33% { transform: translateY(-20px) translateX(10px); } 66% { transform: translateY(-10px) translateX(-10px); } }

.hero-content { position: relative; z-index: 3; max-width: 700px; }

/* BADGE */
.hero-badge {
  display: inline-flex; align-items: center; gap: 10px;
  background: rgba(245,197,24,0.1); border: 1px solid rgba(245,197,24,0.3);
  border-radius: 100px; padding: 8px 18px;
  font-size: 0.75rem; font-weight: 600; color: var(--gold);
  letter-spacing: 0.07em; text-transform: uppercase; margin-bottom: 30px;
  animation: fadeUp 0.6s ease both; backdrop-filter: blur(10px);
}
.hero-badge .pulse-dot { width: 7px; height: 7px; background: var(--gold); border-radius: 50%; box-shadow: 0 0 8px var(--gold); animation: pulse 2s infinite; }
@keyframes pulse { 0%,100% { opacity:1; box-shadow: 0 0 8px var(--gold); } 50% { opacity:0.4; box-shadow: 0 0 4px var(--gold); } }

.hero-title { font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: clamp(2.8rem, 6vw, 4.8rem); font-weight: 900; line-height: 1.07; letter-spacing: -0.035em; margin-bottom: 26px; animation: fadeUp 0.6s 0.1s ease both; }
.hero-title .line-ghost { display: block; -webkit-text-stroke: 2px rgba(245,197,24,0.5); color: transparent; transition: all 0.4s; }
.hero-title:hover .line-ghost { -webkit-text-stroke-color: rgba(245,197,24,0.8); }
.hero-desc { font-size: 1.08rem; font-weight: 300; color: rgba(255,255,255,0.72); line-height: 1.78; max-width: 540px; margin-bottom: 42px; animation: fadeUp 0.6s 0.2s ease both; }
.hero-actions { display: flex; gap: 14px; flex-wrap: wrap; animation: fadeUp 0.6s 0.3s ease both; }

/* HERO FLOATING CARD */
.hero-float-card {
  position: absolute; right: 5vw; top: 50%; transform: translateY(-50%);
  z-index: 3; width: 280px;
  background: rgba(17,32,96,0.75); border: 1px solid rgba(245,197,24,0.2);
  border-radius: 20px; padding: 24px;
  backdrop-filter: blur(20px);
  box-shadow: 0 32px 64px rgba(0,0,0,0.5), 0 0 0 1px rgba(245,197,24,0.08);
  animation: fadeUp 0.7s 0.4s ease both;
}
.hero-float-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, transparent, var(--gold), transparent); border-radius: 20px 20px 0 0; }
.hfc-title { font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 0.78rem; font-weight: 700; color: rgba(255,255,255,0.5); letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.hfc-title::before { content: ''; width: 6px; height: 6px; background: var(--gold); border-radius: 50%; box-shadow: 0 0 8px var(--gold); animation: pulse 2s infinite; }
.hfc-rooms { display: flex; flex-direction: column; gap: 10px; }
.hfc-room { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: rgba(255,255,255,0.04); border-radius: 10px; border: 1px solid rgba(255,255,255,0.06); }
.hfc-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.hfc-dot.on  { background: #22c55e; box-shadow: 0 0 10px rgba(34,197,94,0.6); animation: pulse 2s infinite; }
.hfc-dot.off { background: rgba(255,255,255,0.2); }
.hfc-dot.locked { background: #f87171; box-shadow: 0 0 8px rgba(248,113,113,0.5); }
.hfc-room-text { flex: 1; }
.hfc-room-name { font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 0.78rem; font-weight: 600; }
.hfc-room-sub  { font-size: 0.67rem; color: rgba(255,255,255,0.4); margin-top: 1px; }
.hfc-pill { font-size: 0.62rem; font-weight: 700; padding: 3px 9px; border-radius: 100px; letter-spacing: 0.04em; white-space: nowrap; }
.pill-on   { background: rgba(34,197,94,0.12); color: #4ade80; }
.pill-off  { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.5); }
.pill-lock { background: rgba(248,113,113,0.1); color: #fca5a5; }

@keyframes fadeUp { from { opacity: 0; transform: translateY(28px); } to { opacity: 1; transform: translateY(0); } }

/* BUTTONS */
.btn-primary { display: inline-flex; align-items: center; gap: 9px; background: var(--gold); color: var(--navy); font-family: 'Poppins', Arial, Helvetica, sans-serif; font-weight: 700; font-size: 0.95rem; padding: 15px 30px; border-radius: 12px; text-decoration: none; transition: all 0.3s cubic-bezier(0.16,1,0.3,1); box-shadow: 0 8px 30px rgba(245,197,24,0.3); position: relative; overflow: hidden; }
.btn-primary:hover { background: var(--gold-pale); transform: translateY(-3px); box-shadow: 0 16px 40px rgba(245,197,24,0.42); }
.btn-secondary { display: inline-flex; align-items: center; gap: 9px; background: rgba(255,255,255,0.05); color: var(--white); font-family: 'Poppins', Arial, Helvetica, sans-serif; font-weight: 600; font-size: 0.95rem; padding: 15px 30px; border-radius: 12px; border: 1.5px solid rgba(255,255,255,0.14); text-decoration: none; transition: all 0.3s cubic-bezier(0.16,1,0.3,1); backdrop-filter: blur(8px); }
.btn-secondary:hover { border-color: var(--gold); color: var(--gold); background: rgba(245,197,24,0.06); transform: translateY(-3px); box-shadow: 0 8px 30px rgba(245,197,24,0.15); }

/* ════════════════════════════════════
   STATS STRIP
════════════════════════════════════ */
.stats-strip { position: relative; z-index: 2; border-top: 1px solid rgba(245,197,24,0.08); border-bottom: 1px solid rgba(245,197,24,0.08); background: linear-gradient(135deg, rgba(17,32,96,0.55) 0%, rgba(11,22,64,0.65) 100%); backdrop-filter: blur(14px); padding: 36px 5vw; display: flex; justify-content: center; gap: 0; flex-wrap: wrap; }
.stat-item { text-align: center; padding: 8px 40px; transition: transform 0.3s; display: flex; flex-direction: column; align-items: center; gap: 8px; }
.stat-item:hover { transform: translateY(-4px); }
.stat-icon { width: 42px; height: 42px; border-radius: 12px; background: rgba(245,197,24,0.1); border: 1px solid rgba(245,197,24,0.2); display: flex; align-items: center; justify-content: center; margin-bottom: 4px; }
.stat-num { font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 2.2rem; font-weight: 800; background: linear-gradient(135deg, var(--gold) 0%, var(--gold-pale) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1; display: block; }
.stat-label { font-size: 0.76rem; color: rgba(255,255,255,0.45); font-weight: 500; text-transform: uppercase; letter-spacing: 0.1em; }
.stat-divider { width: 1px; background: linear-gradient(to bottom, transparent, rgba(245,197,24,0.2), transparent); align-self: stretch; margin: 8px 0; }

/* ════════════════════════════════════
   SECTION BASE
════════════════════════════════════ */
section { position: relative; z-index: 2; }
.section-label { display: inline-flex; align-items: center; gap: 10px; color: var(--gold); font-size: 0.74rem; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; margin-bottom: 14px; }
.section-label::before { content: ''; width: 24px; height: 2px; background: linear-gradient(90deg, var(--gold), rgba(245,197,24,0.3)); border-radius: 2px; }
.section-title { font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: clamp(1.8rem, 3.5vw, 2.75rem); font-weight: 800; line-height: 1.1; letter-spacing: -0.025em; margin-bottom: 14px; }
.section-desc { font-size: 1rem; color: rgba(255,255,255,0.52); line-height: 1.78; max-width: 500px; }

/* ════════════════════════════════════
   PROBLEM SECTION
════════════════════════════════════ */
.problem-section { padding: 110px 5vw; background: linear-gradient(180deg, var(--navy) 0%, var(--navy-mid) 100%); position: relative; }
.problem-section::before { content: ''; position: absolute; top: -1px; left: 0; right: 0; height: 180px; background: linear-gradient(to bottom, var(--navy) 0%, transparent 100%); pointer-events: none; z-index: 1; }
.problem-inner { max-width: 1100px; margin: 0 auto; position: relative; z-index: 2; }
.problem-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 52px; }
.problem-card {
  background: linear-gradient(135deg, rgba(17,32,96,0.5) 0%, rgba(11,22,64,0.6) 100%);
  border: 1px solid rgba(245,197,24,0.1); border-radius: 20px; padding: 32px;
  transition: all 0.4s cubic-bezier(0.16,1,0.3,1); position: relative; overflow: hidden;
  display: flex; gap: 20px; align-items: flex-start;
}
.problem-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, transparent, var(--gold), transparent); opacity: 0; transition: opacity 0.4s; }
.problem-card:hover { transform: translateY(-5px); border-color: rgba(245,197,24,0.3); box-shadow: 0 20px 50px rgba(0,0,0,0.35), 0 0 0 1px rgba(245,197,24,0.08); }
.problem-card:hover::before { opacity: 1; }
.problem-icon-wrap { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; background: rgba(245,197,24,0.1); border: 1px solid rgba(245,197,24,0.18); flex-shrink: 0; transition: all 0.3s; }
.problem-card:hover .problem-icon-wrap { background: rgba(245,197,24,0.16); border-color: rgba(245,197,24,0.35); transform: scale(1.06); }
.problem-text h3 { font-family: 'Poppins', Arial, Helvetica, sans-serif; font-weight: 700; font-size: 1.02rem; margin-bottom: 8px; }
.problem-text p { font-size: 0.86rem; color: rgba(255,255,255,0.52); line-height: 1.7; }

/* ════════════════════════════════════
   FEATURES
════════════════════════════════════ */
.features-section { padding: 110px 5vw; background: var(--navy); position: relative; }
.features-section::before { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); width: 900px; height: 900px; background: radial-gradient(circle, rgba(245,197,24,0.025) 0%, transparent 60%); pointer-events: none; }
.features-header { text-align: center; max-width: 600px; margin: 0 auto 68px; display: flex; flex-direction: column; align-items: center; }
.features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; max-width: 1100px; margin: 0 auto; position: relative; z-index: 1; }
.feature-card {
  background: linear-gradient(145deg, rgba(17,32,96,0.75) 0%, rgba(11,22,64,0.9) 100%);
  border: 1px solid rgba(245,197,24,0.09); border-radius: 20px; padding: 32px 26px;
  position: relative; overflow: hidden; transition: all 0.4s cubic-bezier(0.16,1,0.3,1);
}
.feature-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, transparent, var(--gold), transparent); opacity: 0; transition: opacity 0.4s; }
.feature-card:hover { transform: translateY(-8px); border-color: rgba(245,197,24,0.28); box-shadow: 0 28px 56px rgba(0,0,0,0.35); }
.feature-card:hover::before { opacity: 1; }
.feature-card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.feature-icon-box { width: 48px; height: 48px; border-radius: 13px; background: rgba(245,197,24,0.1); border: 1px solid rgba(245,197,24,0.18); display: flex; align-items: center; justify-content: center; transition: all 0.3s; }
.feature-card:hover .feature-icon-box { background: rgba(245,197,24,0.18); border-color: rgba(245,197,24,0.4); transform: scale(1.08) rotate(-4deg); }
.feature-num { font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 2.8rem; font-weight: 900; color: rgba(245,197,24,0.07); line-height: 1; transition: color 0.3s; }
.feature-card:hover .feature-num { color: rgba(245,197,24,0.13); }
.feature-card h3 { font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 1.02rem; font-weight: 700; margin-bottom: 9px; color: var(--white); }
.feature-card p { font-size: 0.85rem; color: rgba(255,255,255,0.5); line-height: 1.72; }
.feature-tag { display: inline-flex; align-items: center; gap: 5px; margin-top: 18px; padding: 5px 12px; border-radius: 100px; background: rgba(245,197,24,0.08); border: 1px solid rgba(245,197,24,0.18); font-size: 0.67rem; font-weight: 700; color: var(--gold); text-transform: uppercase; letter-spacing: 0.08em; transition: all 0.3s; }
.feature-card:hover .feature-tag { background: rgba(245,197,24,0.15); border-color: rgba(245,197,24,0.38); }

/* ════════════════════════════════════
   HOW IT WORKS
════════════════════════════════════ */
.how-section { padding: 110px 5vw; background: linear-gradient(180deg, var(--navy-mid) 0%, var(--navy) 100%); }
.how-header { text-align: center; max-width: 580px; margin: 0 auto 80px; display: flex; flex-direction: column; align-items: center; }
.steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; max-width: 1100px; margin: 0 auto; position: relative; }
.steps::before { content: ''; position: absolute; top: 50px; left: 12.5%; right: 12.5%; height: 2px; background: linear-gradient(90deg, var(--gold), rgba(245,197,24,0.3), rgba(245,197,24,0.08)); z-index: 0; }
.step { text-align: center; padding: 0 14px; position: relative; z-index: 1; }
.step-badge {
  width: 100px; height: 100px; border-radius: 50%;
  background: linear-gradient(135deg, var(--navy-mid) 0%, var(--navy) 100%);
  border: 2px solid rgba(245,197,24,0.4);
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px;
  margin: 0 auto 24px;
  transition: all 0.4s cubic-bezier(0.16,1,0.3,1);
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
.step:hover .step-badge { background: var(--gold); border-color: var(--gold); box-shadow: 0 0 40px rgba(245,197,24,0.4), 0 8px 28px rgba(0,0,0,0.3); transform: scale(1.08); }
.step-badge-num { font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 0.68rem; font-weight: 700; color: rgba(245,197,24,0.6); letter-spacing: 0.1em; line-height: 1; transition: color 0.3s; }
.step:hover .step-badge-num { color: rgba(11,22,64,0.7); }
.step-badge-icon { transition: all 0.3s; }
.step:hover .step-badge-icon path,
.step:hover .step-badge-icon rect,
.step:hover .step-badge-icon circle { stroke: var(--navy) !important; }
.step:hover .step-badge-icon .fill-gold { fill: var(--navy) !important; }
.step h3 { font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 0.98rem; font-weight: 700; margin-bottom: 10px; }
.step p { font-size: 0.81rem; color: rgba(255,255,255,0.48); line-height: 1.65; }

/* ════════════════════════════════════
   OBJECTIVES
════════════════════════════════════ */
.objectives-outer { padding: 110px 5vw; background: var(--navy); }
.objectives-section { display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: center; max-width: 1200px; margin: 0 auto; }
.obj-list { margin-top: 28px; display: flex; flex-direction: column; gap: 14px; }
.obj-item {
  display: flex; gap: 16px; align-items: flex-start; padding: 20px 22px;
  background: linear-gradient(135deg, rgba(17,32,96,0.4) 0%, rgba(11,22,64,0.5) 100%);
  border: 1px solid rgba(245,197,24,0.08); border-radius: 16px;
  transition: all 0.35s cubic-bezier(0.16,1,0.3,1);
}
.obj-item:hover { border-color: rgba(245,197,24,0.28); background: linear-gradient(135deg, rgba(245,197,24,0.05) 0%, rgba(17,32,96,0.5) 100%); transform: translateX(6px); box-shadow: -4px 0 0 var(--gold), 0 10px 30px rgba(0,0,0,0.18); }
.obj-icon { width: 38px; height: 38px; border-radius: 11px; background: linear-gradient(135deg, var(--gold) 0%, var(--gold-deep) 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 12px rgba(245,197,24,0.28); }
.obj-item p { font-size: 0.88rem; color: rgba(255,255,255,0.68); line-height: 1.68; }

/* STATUS CARD */
.access-card {
  background: linear-gradient(145deg, rgba(17,32,96,0.88), rgba(11,22,64,0.96));
  border: 1px solid rgba(245,197,24,0.2); border-radius: 24px; padding: 30px;
  box-shadow: 0 40px 80px rgba(0,0,0,0.5), 0 0 0 1px rgba(245,197,24,0.06);
  position: relative; overflow: hidden;
}
.access-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, transparent, var(--gold), transparent); }
.access-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
.access-title { font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 0.9rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.access-live { display: flex; align-items: center; gap: 6px; font-size: 0.68rem; font-weight: 700; color: #4ade80; letter-spacing: 0.08em; }
.access-live::before { content: ''; width: 6px; height: 6px; background: #4ade80; border-radius: 50%; box-shadow: 0 0 8px rgba(74,222,128,0.7); animation: pulse 2s infinite; }
.access-rooms { display: flex; flex-direction: column; gap: 9px; }
.access-room { display: flex; align-items: center; gap: 12px; padding: 13px 15px; background: rgba(255,255,255,0.03); border-radius: 13px; border: 1px solid rgba(255,255,255,0.05); transition: all 0.3s; }
.access-room:hover { background: rgba(255,255,255,0.06); border-color: rgba(245,197,24,0.14); transform: translateX(4px); }
.room-status-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }
.dot-on   { background: #22c55e; box-shadow: 0 0 10px rgba(34,197,94,0.6); animation: pulse 2s infinite; }
.dot-off  { background: rgba(255,255,255,0.18); }
.dot-lock { background: #f87171; box-shadow: 0 0 8px rgba(248,113,113,0.5); }
.access-room-info { flex: 1; }
.access-room-name { font-family: 'Poppins', Arial, Helvetica, sans-serif; font-size: 0.82rem; font-weight: 600; }
.access-room-sub  { font-size: 0.69rem; color: rgba(255,255,255,0.38); margin-top: 2px; }
.access-room-status { font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 100px; letter-spacing: 0.04em; white-space: nowrap; }
.s-live { background: rgba(34,197,94,0.12); color: #4ade80; }
.s-free { background: rgba(148,163,184,0.1); color: rgba(255,255,255,0.5); }
.s-lock { background: rgba(248,113,113,0.1); color: #fca5a5; }

/* ════════════════════════════════════
   CTA
════════════════════════════════════ */
.cta-section { padding: 120px 5vw; text-align: center; position: relative; overflow: hidden; background: linear-gradient(180deg, var(--navy) 0%, var(--navy-mid) 50%, var(--navy) 100%); }
.cta-section::before { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); width: 700px; height: 700px; background: radial-gradient(circle, rgba(245,197,24,0.09) 0%, transparent 60%); pointer-events: none; animation: float 18s ease-in-out infinite; }
.cta-section::after { content: ''; position: absolute; inset: 0; background-image: linear-gradient(rgba(245,197,24,0.018) 1px, transparent 1px), linear-gradient(90deg, rgba(245,197,24,0.018) 1px, transparent 1px); background-size: 60px 60px; mask-image: radial-gradient(ellipse 65% 65% at 50% 50%, black 0%, transparent 100%); pointer-events: none; }
.cta-section .section-title { font-size: clamp(2rem, 4vw, 3.2rem); position: relative; z-index: 1; }
.cta-section .section-desc { max-width: 520px; margin: 0 auto 44px; position: relative; z-index: 1; }
.cta-actions { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; position: relative; z-index: 1; }

/* ════════════════════════════════════
   FOOTER
════════════════════════════════════ */
.footer-new {
  background: rgba(5,10,30,0.99);
  border-top: 1px solid rgba(245,197,24,0.1);
  padding: 56px 5vw 0;
  font-family: 'DM Sans', sans-serif;
  color: rgba(255,255,255,0.55);
  position: relative;
  z-index: 2;
}
.footer-top {
  display: grid;
  grid-template-columns: repeat(4, 1fr) 1.4fr;
  gap: 40px;
  padding-bottom: 48px;
  border-bottom: 1px solid rgba(245,197,24,0.08);
}
.footer-col-heading {
  font-family: 'Sora', sans-serif;
  font-weight: 700;
  font-size: 0.78rem;
  color: #fff;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 16px;
}
.footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 9px; }
.footer-col ul li a {
  color: rgba(255,255,255,0.5);
  font-size: 0.84rem;
  text-decoration: none;
  transition: color 0.2s;
}
.footer-col ul li a:hover { color: var(--gold); }
.footer-member-name {
  color: rgba(255,255,255,0.5);
  font-size: 0.84rem;
  line-height: 1;
}
.footer-newsletter-title {
  font-family: 'Sora', sans-serif;
  font-weight: 700;
  font-size: 0.88rem;
  color: #fff;
  margin-bottom: 8px;
}
.footer-newsletter-desc {
  font-size: 0.78rem;
  color: rgba(255,255,255,0.42);
  line-height: 1.6;
  margin-bottom: 16px;
}
.footer-input-row { display: flex; gap: 8px; }
.footer-email-input {
  flex: 1;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(245,197,24,0.2);
  border-radius: 10px;
  padding: 10px 14px;
  color: #fff;
  font-size: 0.82rem;
  outline: none;
  font-family: 'DM Sans', sans-serif;
  transition: border-color 0.2s;
}
.footer-email-input:focus { border-color: rgba(245,197,24,0.6); }
.footer-subscribe-btn {
  background: var(--gold);
  color: var(--navy);
  font-family: 'Sora', sans-serif;
  font-weight: 700;
  font-size: 0.8rem;
  border: none;
  border-radius: 10px;
  padding: 10px 16px;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.25s;
  box-shadow: 0 4px 14px rgba(245,197,24,0.3);
}
.footer-subscribe-btn:hover { background: var(--gold-pale); transform: translateY(-2px); }
.footer-socials { display: flex; gap: 10px; margin-top: 20px; align-items: center; }
.footer-social-icon {
  width: 34px; height: 34px; border-radius: 8px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(245,197,24,0.12);
  display: flex; align-items: center; justify-content: center;
  transition: all 0.25s; text-decoration: none;
}
.footer-social-icon:hover { background: rgba(245,197,24,0.14); border-color: rgba(245,197,24,0.4); }
.footer-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 0;
  flex-wrap: wrap;
  gap: 12px;
}
.footer-bottom-brand { display: flex; align-items: center; gap: 12px; }
.footer-bottom-logo { width: 32px; height: 32px; background: var(--gold); border-radius: 9px; display: flex; align-items: center; justify-content: center; }
.footer-bottom-name { font-family: 'Sora', sans-serif; font-weight: 700; font-size: 0.95rem; color: #fff; }
.footer-bottom-name span { color: var(--gold); }
.footer-copy { font-size: 0.74rem; color: rgba(255,255,255,0.28); }
.footer-legal { display: flex; gap: 20px; align-items: center; }
.footer-legal a { font-size: 0.74rem; color: rgba(255,255,255,0.32); text-decoration: none; transition: color 0.2s; }
.footer-legal a:hover { color: var(--gold); }

/* ════════════════════════════════════
   RESPONSIVE
════════════════════════════════════ */
@media (max-width: 1100px) {
  .hero-float-card { display: none; }
  .features-grid { grid-template-columns: 1fr 1fr; }
  .steps { grid-template-columns: 1fr 1fr; gap: 40px; }
  .steps::before { display: none; }
  .objectives-section { grid-template-columns: 1fr; }
  .footer-top { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 700px) {
  nav { padding: 0 20px; }
  .nav-links { display: none; }
  .nav-hamburger { display: flex; }
  .problem-grid { grid-template-columns: 1fr; }
  .features-grid { grid-template-columns: 1fr; }
  .steps { grid-template-columns: 1fr 1fr; }
  .stats-strip { gap: 0; }
  .stat-item { padding: 8px 20px; }
  .stat-divider { display: none; }
  .footer-top { grid-template-columns: 1fr 1fr; gap: 28px; }
  .footer-bottom { flex-direction: column; text-align: center; }
  .footer-legal { flex-wrap: wrap; justify-content: center; }
}
</style>
@include('partials.pro-motion')
</head>
<body>

<!-- ════════════ NAV ════════════ -->
<nav id="mainNav">

  <a href="#" class="sidebar-logo">
    <div class="logo-mark" style="padding:0; background:none; box-shadow:none;">
      <img src="{{ asset('images/logo.png') }}" alt="SmartRoom Logo" style="width:42px; height:42px; display:block; border-radius:12px; background:var(--yellow);" />
    </div>
    <div class="logo-text">
      <span class="brand-psu">PSU · Asingan</span>
      <span class="brand-main">Smart<span>Room</span></span>
    </div>
  </a>

  <ul class="nav-links">
    <li><a href="#">Home</a></li>
    <li><a href="#problems">Problem</a></li>
    <li><a href="#features">Features</a></li>
    <li><a href="#how">How It Works</a></li>
    <li><a href="#objectives">Objectives</a></li>
    <li>
      <a href="{{ route('auth.login') }}" class="nav-cta">
        <svg width="13" height="13" viewBox="0 0 16 16" fill="none"><path d="M10 2h3a1 1 0 011 1v10a1 1 0 01-1 1h-3M7 11l3-3-3-3M10 8H2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Sign In
      </a>
    </li>
  </ul>

  <button class="nav-hamburger" id="hamburger" aria-label="Menu">
    <span></span><span></span><span></span>
  </button>
</nav>

<!-- ════════════ HERO ════════════ -->
<section class="hero">
  <div class="hero-bg">
    <img src="{{ asset('images/psu.png') }}" alt="PSU Asingan Campus" />
  </div>
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>

  <div class="hero-content">
    <div class="hero-badge">
      <span class="pulse-dot"></span>
      Pangasinan State University – Asingan Campus
    </div>
    <h1 class="hero-title">
      Intelligent Rooms,<br>
      <span style="color:var(--gold);">Zero Conflicts.</span><br>
      <span class="line-ghost">Seamless Access.</span>
    </h1>
    <p class="hero-desc">
      SmartRoom is a web-based classroom scheduling system with RFID-controlled access — eliminating double bookings, unauthorized entry, and classroom confusion at PSU Asingan.
    </p>
    <div class="hero-actions">
      <a href="#features" class="btn-primary">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><rect x="1" y="1" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.6"/><rect x="9" y="1" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.6"/><rect x="1" y="9" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.6"/><rect x="9" y="9" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.6"/></svg>
        Explore Features
      </a>
      <a href="#how" class="btn-secondary">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.5"/><path d="M6.5 5.5l4 2.5-4 2.5V5.5z" fill="currentColor"/></svg>
        See How It Works
      </a>
    </div>
  </div>

  <!-- Floating live status card -->
  <div class="hero-float-card">
    <div class="hfc-title">Live Room Status</div>
    <div class="hfc-rooms">
      <div class="hfc-room">
        <div class="hfc-dot on"></div>
        <div class="hfc-room-text">
          <div class="hfc-room-name">Room 101 – CIT Lab</div>
          <div class="hfc-room-sub">Prof. Santos · BSIT 2A</div>
        </div>
        <span class="hfc-pill pill-on">OCCUPIED</span>
      </div>
      <div class="hfc-room">
        <div class="hfc-dot off"></div>
        <div class="hfc-room-text">
          <div class="hfc-room-name">Room 102 – Lecture A</div>
          <div class="hfc-room-sub">Next: 10:00 AM</div>
        </div>
        <span class="hfc-pill pill-off">VACANT</span>
      </div>
      <div class="hfc-room">
        <div class="hfc-dot locked"></div>
        <div class="hfc-room-text">
          <div class="hfc-room-name">Room 203 – Science Lab</div>
          <div class="hfc-room-sub">Reserved: 1:00 PM</div>
        </div>
        <span class="hfc-pill pill-lock">LOCKED</span>
      </div>
    </div>
  </div>
</section>

<!-- ════════════ STATS ════════════ -->
<div class="stats-strip">
  <div class="stat-item reveal">
    <div class="stat-icon">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="8" stroke="#f5c518" stroke-width="1.5"/><path d="M7 10l2 2 4-4" stroke="#f5c518" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <span class="stat-num">0</span>
    <span class="stat-label">Conflicts</span>
  </div>
  <div class="stat-divider"></div>
  <div class="stat-item reveal reveal-delay-1">
    <div class="stat-icon">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="7.5" stroke="#f5c518" stroke-width="1.5" stroke-dasharray="2 2"/><circle cx="10" cy="10" r="3" stroke="#f5c518" stroke-width="1.5"/><circle cx="10" cy="10" r=".8" fill="#f5c518"/></svg>
    </div>
    <span class="stat-num">100%</span>
    <span class="stat-label">Real-time</span>
  </div>
  <div class="stat-divider"></div>
  <div class="stat-item reveal reveal-delay-2">
    <div class="stat-icon">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><rect x="3" y="2" width="10" height="14" rx="1.5" stroke="#f5c518" stroke-width="1.5"/><path d="M13 6l3.5 1.5v5L13 14" stroke="#f5c518" stroke-width="1.5" stroke-linejoin="round"/><circle cx="10" cy="9" r="1" fill="#f5c518"/></svg>
    </div>
    <span class="stat-num">RFID</span>
    <span class="stat-label">Smart Access</span>
  </div>
  <div class="stat-divider"></div>
  <div class="stat-item reveal reveal-delay-3">
    <div class="stat-icon">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="7.5" stroke="#f5c518" stroke-width="1.5"/><path d="M10 5.5V10l3 2" stroke="#f5c518" stroke-width="1.5" stroke-linecap="round"/></svg>
    </div>
    <span class="stat-num">24/7</span>
    <span class="stat-label">Access Logs</span>
  </div>
</div>

<!-- ════════════ PROBLEM ════════════ -->
<section class="problem-section" id="problems">
  <div class="problem-inner">
    <div class="section-label reveal">The Problem</div>
    <h2 class="section-title reveal">Why PSU Asingan Needs SmartRoom</h2>
    <p class="section-desc reveal">Traditional classroom management creates recurring problems that disrupt learning and administrative efficiency.</p>
    <div class="problem-grid">

      <div class="problem-card reveal reveal-delay-1">
        <div class="problem-text">
          <h3>Scheduling Conflicts & Double Booking</h3>
          <p>Classroom schedules prepared in advance still result in conflicts due to human error, no real-time tracking, and lack of a centralized conflict-prevention system.</p>
        </div>
      </div>

      <div class="problem-card reveal reveal-delay-2">
        <div class="problem-text">
          <h3>Unauthorized Room Use</h3>
          <p>Without automated access control, classrooms can be entered by unauthorized individuals during or outside scheduled hours, compromising security.</p>
        </div>
      </div>

      <div class="problem-card reveal reveal-delay-3">
        <div class="problem-text">
          <h3>No Centralized Monitoring</h3>
          <p>Faculty and administrators have no unified view of room availability, reservations, and access logs — leading to inefficient decision-making.</p>
        </div>
      </div>

      <div class="problem-card reveal reveal-delay-4">
        <div class="problem-text">
          <h3>Delays & Classroom Inefficiency</h3>
          <p>Confusion from conflicting schedules leads to class delays, wasted classroom time, and reduced overall productivity for students and instructors alike.</p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ════════════ FEATURES ════════════ -->
<section class="features-section" id="features">
  <div class="features-header">
    <div class="section-label reveal">Core Features</div>
    <h2 class="section-title reveal">Everything You Need for Smarter Classrooms</h2>
    <p class="section-desc reveal">A complete integrated system covering scheduling, access control, monitoring, and analytics.</p>
  </div>
  <div class="features-grid">

    <div class="feature-card reveal reveal-delay-1">
      <div class="feature-card-top">
        <span class="feature-num">01</span>
      </div>
      <h3>Admin Dashboard</h3>
      <p>Centralized control panel with real-time room availability, visual schedule management, conflict detection, and automated double-booking prevention.</p>
      <span class="feature-tag">
        <svg width="9" height="9" viewBox="0 0 10 10" fill="none"><circle cx="5" cy="5" r="4" stroke="currentColor" stroke-width="1.5"/><circle cx="5" cy="5" r="1.5" fill="currentColor"/></svg>
        Real-time
      </span>
    </div>

    <div class="feature-card reveal reveal-delay-2">
      <div class="feature-card-top">
        <span class="feature-num">02</span>
      </div>
      <h3>RFID Smart Door Access</h3>
      <p>Instructors tap their RFID card to unlock their assigned classroom only during their scheduled time slot — automatically denied outside of schedule.</p>
      <span class="feature-tag">
        <svg width="9" height="9" viewBox="0 0 10 10" fill="none"><rect x="1" y="1" width="8" height="8" rx="1" stroke="currentColor" stroke-width="1.5"/></svg>
        Hardware
      </span>
    </div>

    <div class="feature-card reveal reveal-delay-3">
      <div class="feature-card-top">
        <span class="feature-num">03</span>
      </div>
      <h3>Room Reservation System</h3>
      <p>Authorized faculty can request and confirm reservations for available classrooms, reducing informal bookings and manual coordination overhead.</p>
      <span class="feature-tag">
        <svg width="9" height="9" viewBox="0 0 10 10" fill="none"><path d="M5 1v4l2.5 1.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="5" cy="5" r="4" stroke="currentColor" stroke-width="1.4"/></svg>
        Self-service
      </span>
    </div>

    <div class="feature-card reveal reveal-delay-1">
      <div class="feature-card-top">
        <span class="feature-num">04</span>
      </div>
      <h3>Occupancy Indicator Light</h3>
      <p>Physical LED indicator on each room shows green when vacant and yellow when occupied — giving anyone in the hallway instant situational awareness.</p>
      <span class="feature-tag">
        <svg width="9" height="9" viewBox="0 0 10 10" fill="none"><path d="M5 1.5C3 1.5 1.5 3 1.5 5S3 8.5 5 8.5 8.5 7 8.5 5 7 1.5 5 1.5z" stroke="currentColor" stroke-width="1.4"/><circle cx="5" cy="5" r="1.5" fill="currentColor"/></svg>
        IoT
      </span>
    </div>

    <div class="feature-card reveal reveal-delay-2">
      <div class="feature-card-top">
        <span class="feature-num">05</span>
      </div>
      <h3>Centralized Access Logs</h3>
      <p>All RFID scan events — granted or denied — are automatically logged with timestamps, instructor identity, and room details for auditing and review.</p>
      <span class="feature-tag">
        <svg width="9" height="9" viewBox="0 0 10 10" fill="none"><path d="M5 1l1.2 2.5L9 4l-2 2 .5 2.8L5 7.5 2.5 8.8 3 6 1 4l2.8-.5z" stroke="currentColor" stroke-width="1.2"/></svg>
        Audit Trail
      </span>
    </div>

    <div class="feature-card reveal reveal-delay-3">
      <div class="feature-card-top">
        <span class="feature-num">06</span>
      </div>
      <h3>RFID Card Registration</h3>
      <p>Admin registers and manages faculty RFID credentials through the web interface. Cards can be enabled, suspended, or revoked at any time instantly.</p>
      <span class="feature-tag">
        <svg width="9" height="9" viewBox="0 0 10 10" fill="none"><rect x="2" y="4" width="6" height="5" rx="1" stroke="currentColor" stroke-width="1.4"/><path d="M3.5 4V3a1.5 1.5 0 013 0v1" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
        Security
      </span>
    </div>

  </div>
</section>

<!-- ════════════ HOW IT WORKS ════════════ -->
<section class="how-section" id="how">
  <div class="how-header">
    <div class="section-label reveal">How It Works</div>
    <h2 class="section-title reveal">Simple. Automated. Secure.</h2>
    <p class="section-desc reveal">From schedule setup to physical door access — SmartRoom handles it end to end.</p>
  </div>
  <div class="steps">

    <div class="step reveal reveal-delay-1">
      <div class="step-badge">
        <span class="step-badge-num">STEP 01</span>
      </div>
      <h3>Admin Builds Schedule</h3>
      <p>Registrar uploads class schedules and assigns rooms via the dashboard with conflict detection active.</p>
    </div>

    <div class="step reveal reveal-delay-2">
      <div class="step-badge">
        <span class="step-badge-num">STEP 02</span>
      </div>
      <h3>RFID Cards Issued</h3>
      <p>Faculty receive RFID cards registered to their identity and linked to their room assignments in the system.</p>
    </div>

    <div class="step reveal reveal-delay-3">
      <div class="step-badge">
        <span class="step-badge-num">STEP 03</span>
      </div>
      <h3>Tap to Unlock</h3>
      <p>Instructors tap their card at the door reader. Access is granted only during their assigned schedule — denied otherwise.</p>
    </div>

    <div class="step reveal reveal-delay-4">
      <div class="step-badge">
        <span class="step-badge-num">STEP 04</span>
      </div>
      <h3>Live Monitoring</h3>
      <p>The dashboard updates in real time showing room occupancy, access events, and upcoming schedules for all stakeholders.</p>
    </div>

  </div>
</section>

<!-- ════════════ OBJECTIVES ════════════ -->
<section class="objectives-outer" id="objectives">
  <div class="objectives-section">
    <div>
      <div class="section-label reveal">Objectives</div>
      <h2 class="section-title reveal">What SmartRoom Sets Out to Achieve</h2>
      <p class="section-desc reveal">Each goal directly addresses the core inefficiencies in PSU's current classroom management workflow.</p>
      <div class="obj-list">

        <div class="obj-item reveal reveal-delay-1">
          <p>Monitor and manage classroom schedules via an admin dashboard with real-time availability, conflict/double-booking prevention, and centralized records.</p>
        </div>

        <div class="obj-item reveal reveal-delay-2">
          <p>Enable authorized faculty members to make classroom reservations for available rooms directly through the system.</p>
        </div>

        <div class="obj-item reveal reveal-delay-3">
          <p>Implement RFID-based time-sensitive door unlocking so only the scheduled instructor can access the room during their assigned period.</p>
        </div>

        <div class="obj-item reveal reveal-delay-4">
          <p>Deploy occupancy indicator lights (ON = occupied, OFF = vacant) to give immediate visual feedback on room status in the hallway.</p>
        </div>

      </div>
    </div>

    <!-- STATUS CARD -->
    <div class="reveal reveal-delay-2">
      <div class="access-card">
        <div class="access-header">
          <div class="access-title">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><rect x="1" y="1" width="14" height="14" rx="2" stroke="#f5c518" stroke-width="1.4"/><path d="M4 8h8M8 4v8" stroke="#f5c518" stroke-width="1.4" stroke-linecap="round" opacity=".5"/><rect x="3" y="3" width="4" height="4" rx=".8" fill="rgba(245,197,24,0.25)"/></svg>
            Live Room Status — PSU Asingan
          </div>
          <div class="access-live">LIVE</div>
        </div>
        <div class="access-rooms">
          <div class="access-room">
            <div class="room-status-dot dot-on"></div>
            <div class="access-room-info">
              <div class="access-room-name">Room 101 – CIT Laboratory</div>
              <div class="access-room-sub">Prof. Santos · BSIT 2A · 7:30–9:00 AM</div>
            </div>
            <div class="access-room-status s-live">OCCUPIED</div>
          </div>
          <div class="access-room">
            <div class="room-status-dot dot-off"></div>
            <div class="access-room-info">
              <div class="access-room-name">Room 102 – Lecture Hall A</div>
              <div class="access-room-sub">Next: Prof. Reyes · 10:00 AM</div>
            </div>
            <div class="access-room-status s-free">VACANT</div>
          </div>
          <div class="access-room">
            <div class="room-status-dot dot-on"></div>
            <div class="access-room-info">
              <div class="access-room-name">Room 201 – Lecture Hall B</div>
              <div class="access-room-sub">Prof. Cruz · BSED 3B · 8:00–9:30 AM</div>
            </div>
            <div class="access-room-status s-live">OCCUPIED</div>
          </div>
          <div class="access-room">
            <div class="room-status-dot dot-lock"></div>
            <div class="access-room-info">
              <div class="access-room-name">Room 203 – Science Lab</div>
              <div class="access-room-sub">Reserved: Prof. Lim · 1:00 PM</div>
            </div>
            <div class="access-room-status s-lock">LOCKED</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ════════════ CTA ════════════ -->
<section class="cta-section">
  <div class="section-label reveal" style="justify-content:center;">Get Started</div>
  <h2 class="section-title reveal">Ready to Modernize Your Campus?</h2>
  <p class="section-desc reveal" style="margin:0 auto 44px;">SmartRoom brings intelligent scheduling and secure RFID access control to Pangasinan State University — Asingan Campus.</p>
  <div class="cta-actions reveal reveal-delay-1">
    <a href="{{ route('auth.login') }}" class="btn-primary" style="font-size:1rem;padding:16px 34px;">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M10 2h3a1 1 0 011 1v10a1 1 0 01-1 1h-3M7 11l3-3-3-3M10 8H2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      Access the System
    </a>
    <a href="#features" class="btn-secondary" style="font-size:1rem;padding:16px 34px;">
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><rect x="1" y="1" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.5"/><rect x="9" y="1" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.5"/><rect x="1" y="9" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.5"/><rect x="9" y="9" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.5"/></svg>
      Learn More
    </a>
  </div>
</section>

<!-- ════════════ FOOTER ════════════ -->
<footer class="footer-new">

  <!-- TOP GRID: 4 link columns + newsletter -->
  <div class="footer-top">

    <!-- Col 1: System -->
    <div class="footer-col">
      <div class="footer-col-heading">System</div>
      <ul>
        <li><a href="#">Dashboard</a></li>
        <li><a href="#">Room Scheduler</a></li>
        <li><a href="#">RFID Management</a></li>
        <li><a href="#">Access Logs</a></li>
        <li><a href="#">Reservations</a></li>
      </ul>
    </div>

    <!-- Col 2: About -->
    <div class="footer-col">
      <div class="footer-col-heading">About</div>
      <ul>
        <li><a href="#">Overview</a></li>
        <li><a href="#">PSU Asingan Campus</a></li>
        <li><a href="#problems">The Problem</a></li>
        <li><a href="#objectives">Objectives</a></li>
        <li><a href="#how">How It Works</a></li>
      </ul>
    </div>

    <!-- Col 3: Support -->
    <div class="footer-col">
      <div class="footer-col-heading">Support</div>
      <ul>
        <li><a href="#">FAQ</a></li>
        <li><a href="#">Contact Admin</a></li>
        <li><a href="#">User Guide</a></li>
        <li><a href="#">Report an Issue</a></li>
      </ul>
    </div>

    <!-- Col 4: Developers / Members -->
    <div class="footer-col">
      <div class="footer-col-heading">Developers</div>
      <div style="display:flex;flex-direction:column;gap:9px;">
        <!-- ✏️ Replace with your actual member names -->
        <span class="footer-member-name">John Kenneth Bagotsay</span>
        <span class="footer-member-name">Francis Natahalie Flores De Leon</span>
        <span class="footer-member-name">John Mar S. Domingo</span>
        <span class="footer-member-name">John Loyd B. Valencia</span>
        <span class="footer-member-name">Ronalyn Peralta</span>
        <span class="footer-member-name">Michael B. Veedor Jr.</span>
      </div>
    </div>

    <!-- Col 5: Newsletter + Socials -->
    <div class="footer-col">
      <div class="footer-newsletter-title">Stay in the Loop</div>
      <p class="footer-newsletter-desc">Get updates on SmartRoom announcements and system upgrades.</p>
      <div class="footer-input-row">
        <input type="email" class="footer-email-input" placeholder="Your email address" />
        <button class="footer-subscribe-btn">Subscribe</button>
      </div>
      <div class="footer-socials">
        <!-- Facebook -->
        <a href="#" class="footer-social-icon">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" stroke="#f5c518" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <!-- Twitter/X -->
        <a href="#" class="footer-social-icon">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z" stroke="#f5c518" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <!-- Instagram -->
        <a href="#" class="footer-social-icon">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="2" y="2" width="20" height="20" rx="5" stroke="#f5c518" stroke-width="1.8"/><circle cx="12" cy="12" r="4" stroke="#f5c518" stroke-width="1.8"/><circle cx="17.5" cy="6.5" r="1" fill="#f5c518"/></svg>
        </a>
        <!-- LinkedIn -->
        <a href="#" class="footer-social-icon">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z" stroke="#f5c518" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="4" cy="4" r="2" stroke="#f5c518" stroke-width="1.8"/></svg>
        </a>
      </div>
    </div>

  </div>

  <!-- BOTTOM BAR -->
  <div class="footer-bottom">
    <div class="footer-bottom-brand">
      <div class="footer-bottom-logo" style="background:none;padding:0;">
        <img src="{{ asset('images/PSU.png') }}" alt="PSU Logo" style="width:32px; height:32px; display:block; border-radius:50%; background:#fff; object-fit:cover; box-shadow:0 2px 8px rgba(0,0,0,0.08);" />
      </div>
      <span class="footer-bottom-name" style="color:#fff; font-weight:400;">Pangasinan State University</span>
    </div>
    <div class="footer-copy">© 2025 SmartRoom · Pangasinan State University – Asingan Campus · All rights reserved.</div>
    <div class="footer-legal">
      <a href="#">Terms of Service</a>
      <a href="#">Privacy Policy</a>
      <a href="#">Accessibility</a>
    </div>
  </div>

</footer>

<script>
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

  const nav = document.getElementById('mainNav');
  window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 60);
  }, { passive: true });

  const hamburger = document.getElementById('hamburger');
  const navLinks  = document.querySelector('.nav-links');
  hamburger.addEventListener('click', () => {
    const open = navLinks.style.display === 'flex';
    navLinks.style.cssText = open
      ? ''
      : 'display:flex;flex-direction:column;position:absolute;top:72px;left:0;right:0;background:rgba(11,22,64,0.98);padding:20px 24px 28px;gap:18px;border-bottom:1px solid rgba(245,197,24,0.12);backdrop-filter:blur(20px);z-index:99;';
  });
</script>

</body>
</html>