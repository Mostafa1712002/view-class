// Tutorial video recorder — drives ViewClass through each video's click-path,
// overlays Arabic captions, and records a real .webm per video via Playwright.
// Usage: node record.js [trackKey]   (default: all flows in flows.js)
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = 'http://viewclass.test';
const OUT = path.join(__dirname, 'videos');
const PW = 'record123';
const ACCOUNTS = {
  'super-admin':  'admin@goldenplatform.com',
  'school-admin': 'manager@alawwal.local',
  'teacher':      'seed_teacher_1@local.test',
  'student':      'test_student_001@viewclass.local',
  'parent':       'test_parent_001@viewclass.local',
};
// Merge Track-0 flows.cjs with any per-track flows-N.cjs that exist.
let flows = require('./flows.cjs');
for (let n = 1; n <= 5; n++) {
  const fp = path.join(__dirname, `flows-${n}.cjs`);
  if (fs.existsSync(fp)) flows = flows.concat(require(fp));
}

const sleep = ms => new Promise(r => setTimeout(r, ms));

// Inject/refresh the caption + title overlay (survives re-injection after navigation).
async function overlay(page, { id, title, caption }) {
  await page.evaluate(({ id, title, caption }) => {
    let bar = document.getElementById('__tut_cap');
    if (!bar) {
      const style = document.createElement('style');
      style.textContent = `
        #__tut_title{position:fixed;top:14px;inset-inline-start:14px;z-index:2147483647;
          background:linear-gradient(135deg,#cfa046,#a97c2a);color:#fff;font:700 15px/1.4 system-ui,sans-serif;
          padding:8px 14px;border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,.25);direction:rtl}
        #__tut_cap{position:fixed;bottom:0;inset-inline:0;z-index:2147483647;
          background:linear-gradient(0deg,rgba(15,23,42,.96),rgba(15,23,42,.82));color:#fff;
          font:600 22px/1.7 system-ui,sans-serif;padding:20px 28px;text-align:center;direction:rtl;
          border-top:3px solid #cfa046;min-height:70px;display:flex;align-items:center;justify-content:center}
        #__tut_cap b{color:#f0c874}`;
      document.head.appendChild(style);
      const t = document.createElement('div'); t.id = '__tut_title'; document.body.appendChild(t);
      bar = document.createElement('div'); bar.id = '__tut_cap'; document.body.appendChild(bar);
    }
    document.getElementById('__tut_title').textContent = `${id} · ${title}`;
    document.getElementById('__tut_cap').innerHTML = caption || '';
  }, { id, title, caption }).catch(() => {});
}

async function login(page, role) {
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
  await page.evaluate(({ email, pw }) => {
    const u = document.querySelector('input[type=text],input[name=email],input[name=login],input[name=username]');
    const p = document.querySelector('input[type=password]');
    u.value = email; p.value = pw;
    u.dispatchEvent(new Event('input', { bubbles: true }));
    p.dispatchEvent(new Event('input', { bubbles: true }));
    [...document.querySelectorAll('button')].find(b => /تسجيل الدخول/.test(b.innerText)).click();
  }, { email: ACCOUNTS[role], pw: PW });
  await page.waitForURL(u => !u.pathname.endsWith('/login'), { timeout: 15000 }).catch(() => {});
  await sleep(1200);
}

// Log in once per role in a throwaway (unrecorded) context and cache the
// authenticated storageState. Recorded contexts reuse it so each video starts
// already logged in — no white login screen at the head of every clip.
const authStates = {};
async function authState(browser, role) {
  if (!role) return undefined;
  if (authStates[role]) return authStates[role];
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 720 }, locale: 'ar' });
  const page = await ctx.newPage();
  await login(page, role);
  authStates[role] = await ctx.storageState();
  await ctx.close();
  return authStates[role];
}

async function recordFlow(browser, flow) {
  const ctx = await browser.newContext({
    viewport: { width: 1280, height: 720 },
    recordVideo: { dir: OUT, size: { width: 1280, height: 720 } },
    locale: 'ar',
    storageState: await authState(browser, flow.role),
  });
  const page = await ctx.newPage();
  try {
    // First paint: land on the flow's opening screen before anything is timed,
    // so the video never opens on a blank about:blank frame.
    if (flow.steps[0]?.goto) {
      await page.goto(BASE + flow.steps[0].goto, { waitUntil: 'networkidle' }).catch(() => {});
    }
    for (let i = 0; i < flow.steps.length; i++) {
      const step = flow.steps[i];
      // Step 0's page is already loaded by the first-paint goto above.
      if (step.goto && i > 0) await page.goto(BASE + step.goto, { waitUntil: 'networkidle' }).catch(() => {});
      // The Vuexy layout scrolls the <body> element, not the window — scroll all
      // three candidates so `scroll` works regardless of which one owns overflow.
      if (step.scroll) await page.evaluate(y => {
        const opt = { top: y, behavior: 'smooth' };
        window.scrollTo(opt);
        document.documentElement.scrollTo && document.documentElement.scrollTo(opt);
        document.body.scrollTo && document.body.scrollTo(opt);
      }, step.scroll).catch(() => {});
      await overlay(page, { id: flow.id, title: flow.title, caption: step.caption });
      await sleep(step.dwell || 4000);
    }
  } catch (e) { console.error(`  ! ${flow.id} error:`, e.message); }
  const vid = page.video();
  await ctx.close();                        // finalizes the .webm
  const src = await vid.path();
  const dest = path.join(OUT, `${flow.id}.webm`);
  fs.renameSync(src, dest);
  return dest;
}

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const only = process.argv[2];
  const list = only ? flows.filter(f => f.id.startsWith(only) || f.track === only) : flows;
  const browser = await chromium.launch();
  console.log(`Recording ${list.length} videos…`);
  for (const flow of list) {
    process.stdout.write(`  ${flow.id} ${flow.title} … `);
    const out = await recordFlow(browser, flow);
    console.log(`✓ ${path.basename(out)}`);
  }
  await browser.close();
  console.log('Done →', OUT);
})();
