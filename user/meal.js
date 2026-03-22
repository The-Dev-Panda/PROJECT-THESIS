/* ═══════════════════════════════════════════════════
   MEAL TRACKER — meal-tracker.js
  Drop this before </body> in myplan.php
═══════════════════════════════════════════════════ */

const MT_DB = [
  { n: "White Rice (cooked)", s: "1 cup (186g)", cal: 242, p: 4, c: 53, f: 0 },
  { n: "Brown Rice (cooked)", s: "1 cup (195g)", cal: 216, p: 5, c: 45, f: 2 },
  { n: "Fried Rice", s: "1 cup (198g)", cal: 290, p: 7, c: 45, f: 8 },
  { n: "White Bread", s: "1 slice (30g)", cal: 80, p: 3, c: 15, f: 1 },
  { n: "Whole Wheat Bread", s: "1 slice (30g)", cal: 70, p: 3, c: 12, f: 1 },
  { n: "Oatmeal (cooked)", s: "1 cup (240g)", cal: 166, p: 6, c: 28, f: 4 },
  { n: "Instant Oats", s: "1 packet (40g)", cal: 150, p: 5, c: 27, f: 3 },
  { n: "Pasta (cooked)", s: "1 cup (140g)", cal: 220, p: 8, c: 43, f: 1 },
  { n: "Spaghetti (cooked)", s: "1 cup (140g)", cal: 220, p: 8, c: 43, f: 1 },
  { n: "Noodles (cooked)", s: "1 cup (160g)", cal: 210, p: 7, c: 40, f: 2 },
  { n: "Pancit Canton", s: "1 pack (65g)", cal: 280, p: 7, c: 40, f: 10 },
  { n: "Pancit Bihon", s: "1 serving (200g)", cal: 300, p: 12, c: 48, f: 7 },
  { n: "Bread Roll", s: "1 piece (50g)", cal: 140, p: 4, c: 26, f: 2 },
  { n: "Corn (cooked)", s: "1 ear (90g)", cal: 90, p: 3, c: 19, f: 1 },
  { n: "Potato (boiled)", s: "1 medium (150g)", cal: 130, p: 3, c: 30, f: 0 },
  { n: "Sweet Potato", s: "1 medium (130g)", cal: 112, p: 2, c: 26, f: 0 },
  {
    n: "French Fries",
    s: "medium serving (117g)",
    cal: 365,
    p: 4,
    c: 48,
    f: 17,
  },
  {
    n: "Chicken Breast (grilled)",
    s: "1 serving (100g)",
    cal: 165,
    p: 31,
    c: 0,
    f: 4,
  },
  {
    n: "Chicken Thigh (grilled)",
    s: "1 serving (100g)",
    cal: 209,
    p: 26,
    c: 0,
    f: 11,
  },
  { n: "Fried Chicken", s: "1 piece (100g)", cal: 260, p: 22, c: 10, f: 15 },
  { n: "Chicken Adobo", s: "1 serving (150g)", cal: 310, p: 30, c: 5, f: 18 },
  {
    n: "Ground Beef (cooked)",
    s: "1 serving (100g)",
    cal: 250,
    p: 26,
    c: 0,
    f: 16,
  },
  { n: "Beef Steak", s: "1 serving (100g)", cal: 271, p: 26, c: 0, f: 18 },
  {
    n: "Pork Chop (grilled)",
    s: "1 serving (100g)",
    cal: 231,
    p: 25,
    c: 0,
    f: 14,
  },
  { n: "Lechon", s: "1 serving (100g)", cal: 310, p: 24, c: 0, f: 24 },
  { n: "Lechon Kawali", s: "1 serving (100g)", cal: 350, p: 20, c: 0, f: 30 },
  { n: "Bacon", s: "2 strips (20g)", cal: 87, p: 6, c: 0, f: 7 },
  { n: "Hotdog", s: "1 piece (50g)", cal: 150, p: 5, c: 2, f: 13 },
  { n: "Longganisa", s: "2 pieces (80g)", cal: 200, p: 10, c: 6, f: 15 },
  { n: "Corned Beef", s: "1/2 cup (100g)", cal: 185, p: 18, c: 2, f: 11 },
  { n: "Spam", s: "2 slices (56g)", cal: 174, p: 7, c: 2, f: 15 },
  {
    n: "Salmon (grilled)",
    s: "1 serving (100g)",
    cal: 208,
    p: 20,
    c: 0,
    f: 13,
  },
  { n: "Tuna (canned)", s: "1/2 cup (100g)", cal: 116, p: 26, c: 0, f: 1 },
  { n: "Sardines (canned)", s: "1 can (100g)", cal: 208, p: 25, c: 0, f: 11 },
  {
    n: "Tilapia (grilled)",
    s: "1 serving (100g)",
    cal: 129,
    p: 26,
    c: 0,
    f: 3,
  },
  { n: "Bangus (grilled)", s: "1 serving (100g)", cal: 148, p: 20, c: 0, f: 7 },
  { n: "Shrimp (cooked)", s: "1 serving (100g)", cal: 99, p: 24, c: 0, f: 1 },
  { n: "Squid Adobo", s: "1 serving (100g)", cal: 175, p: 18, c: 4, f: 9 },
  {
    n: "Fish Fillet (fried)",
    s: "1 serving (100g)",
    cal: 196,
    p: 20,
    c: 6,
    f: 10,
  },
  { n: "Boiled Egg", s: "1 large (50g)", cal: 78, p: 6, c: 1, f: 5 },
  { n: "Fried Egg", s: "1 large (46g)", cal: 90, p: 6, c: 0, f: 7 },
  { n: "Scrambled Eggs", s: "2 eggs (100g)", cal: 148, p: 10, c: 2, f: 11 },
  { n: "Whole Milk", s: "1 cup (240ml)", cal: 149, p: 8, c: 12, f: 8 },
  { n: "Skim Milk", s: "1 cup (240ml)", cal: 83, p: 8, c: 12, f: 0 },
  { n: "Cheese (cheddar)", s: "1 slice (30g)", cal: 113, p: 7, c: 0, f: 9 },
  { n: "Yogurt (plain)", s: "1 cup (245g)", cal: 149, p: 9, c: 12, f: 8 },
  { n: "Greek Yogurt", s: "1 cup (245g)", cal: 130, p: 22, c: 9, f: 0 },
  { n: "Butter", s: "1 tbsp (14g)", cal: 102, p: 0, c: 0, f: 12 },
  { n: "Broccoli", s: "1 cup (91g)", cal: 31, p: 3, c: 6, f: 0 },
  { n: "Spinach", s: "1 cup (30g)", cal: 7, p: 1, c: 1, f: 0 },
  { n: "Tomato", s: "1 medium (123g)", cal: 22, p: 1, c: 5, f: 0 },
  { n: "Carrot", s: "1 medium (61g)", cal: 25, p: 1, c: 6, f: 0 },
  { n: "Cucumber", s: "1 cup (119g)", cal: 16, p: 1, c: 4, f: 0 },
  { n: "Cabbage", s: "1 cup (89g)", cal: 22, p: 1, c: 5, f: 0 },
  { n: "Eggplant", s: "1 cup (99g)", cal: 35, p: 1, c: 9, f: 0 },
  { n: "Kangkong", s: "1 cup (56g)", cal: 19, p: 2, c: 3, f: 0 },
  { n: "Ampalaya", s: "1 cup (93g)", cal: 24, p: 1, c: 5, f: 0 },
  { n: "Sayote", s: "1 medium (200g)", cal: 38, p: 2, c: 9, f: 0 },
  { n: "Pechay", s: "1 cup (70g)", cal: 15, p: 1, c: 2, f: 0 },
  { n: "Onion", s: "1 medium (110g)", cal: 44, p: 1, c: 10, f: 0 },
  { n: "Banana", s: "1 piece (118g)", cal: 105, p: 1, c: 27, f: 0 },
  { n: "Apple", s: "1 medium (182g)", cal: 95, p: 0, c: 25, f: 0 },
  { n: "Mango", s: "1 cup (165g)", cal: 99, p: 1, c: 25, f: 1 },
  { n: "Papaya", s: "1 cup (140g)", cal: 55, p: 1, c: 14, f: 0 },
  { n: "Watermelon", s: "1 cup (152g)", cal: 46, p: 1, c: 12, f: 0 },
  { n: "Orange", s: "1 medium (131g)", cal: 62, p: 1, c: 15, f: 0 },
  { n: "Avocado", s: "1/2 fruit (100g)", cal: 160, p: 2, c: 9, f: 15 },
  { n: "Pineapple", s: "1 cup (165g)", cal: 82, p: 1, c: 22, f: 0 },
  { n: "Grapes", s: "1 cup (151g)", cal: 104, p: 1, c: 27, f: 0 },
  { n: "Strawberry", s: "1 cup (152g)", cal: 49, p: 1, c: 12, f: 0 },
  { n: "Peanut Butter", s: "2 tbsp (32g)", cal: 191, p: 7, c: 7, f: 16 },
  { n: "Almonds", s: "1/4 cup (35g)", cal: 207, p: 8, c: 8, f: 18 },
  { n: "Peanuts", s: "1/4 cup (37g)", cal: 214, p: 9, c: 6, f: 18 },
  { n: "Mixed Nuts", s: "1/4 cup (35g)", cal: 219, p: 6, c: 8, f: 20 },
  { n: "Tofu", s: "1 serving (100g)", cal: 76, p: 8, c: 2, f: 4 },
  { n: "Potato Chips", s: "1 serving (28g)", cal: 152, p: 2, c: 15, f: 10 },
  { n: "Chocolate Bar", s: "1 bar (44g)", cal: 235, p: 3, c: 26, f: 13 },
  { n: "Cookies", s: "2 pieces (30g)", cal: 142, p: 2, c: 20, f: 7 },
  { n: "Pancake", s: "1 piece (77g)", cal: 175, p: 4, c: 28, f: 6 },
  { n: "Burger", s: "1 regular (200g)", cal: 490, p: 27, c: 40, f: 25 },
  { n: "Cheeseburger", s: "1 piece (113g)", cal: 303, p: 17, c: 24, f: 13 },
  { n: "Pizza (cheese)", s: "1 slice (107g)", cal: 272, p: 12, c: 34, f: 10 },
  {
    n: "Sandwich (ham & cheese)",
    s: "1 piece (150g)",
    cal: 350,
    p: 18,
    c: 33,
    f: 15,
  },
  { n: "Donut", s: "1 piece (60g)", cal: 253, p: 4, c: 30, f: 14 },
  { n: "Croissant", s: "1 piece (57g)", cal: 231, p: 5, c: 26, f: 12 },
  { n: "Granola Bar", s: "1 bar (47g)", cal: 193, p: 4, c: 29, f: 7 },
  { n: "Orange Juice", s: "1 cup (248ml)", cal: 112, p: 2, c: 26, f: 0 },
  { n: "Protein Shake", s: "1 scoop (30g)", cal: 120, p: 24, c: 3, f: 2 },
  { n: "Coffee (black)", s: "1 cup (240ml)", cal: 2, p: 0, c: 0, f: 0 },
  { n: "Coffee with Milk", s: "1 cup (240ml)", cal: 50, p: 2, c: 5, f: 2 },
  { n: "Soda / Cola", s: "1 can (355ml)", cal: 140, p: 0, c: 39, f: 0 },
  {
    n: "Adobo (pork/chicken)",
    s: "1 serving (150g)",
    cal: 320,
    p: 28,
    c: 5,
    f: 20,
  },
  { n: "Sinigang", s: "1 bowl (300g)", cal: 190, p: 20, c: 10, f: 8 },
  { n: "Tinola", s: "1 bowl (300g)", cal: 180, p: 22, c: 8, f: 6 },
  { n: "Menudo", s: "1 serving (150g)", cal: 290, p: 22, c: 15, f: 16 },
  { n: "Caldereta", s: "1 serving (200g)", cal: 380, p: 25, c: 18, f: 22 },
  { n: "Kare-kare", s: "1 serving (200g)", cal: 340, p: 22, c: 12, f: 22 },
  { n: "Nilaga", s: "1 bowl (300g)", cal: 200, p: 20, c: 10, f: 8 },
  { n: "Arroz Caldo", s: "1 bowl (250g)", cal: 230, p: 12, c: 38, f: 4 },
  { n: "Goto", s: "1 bowl (250g)", cal: 210, p: 14, c: 30, f: 5 },
  { n: "Champorado", s: "1 cup (200g)", cal: 220, p: 4, c: 44, f: 4 },
  { n: "Lugaw", s: "1 bowl (250g)", cal: 160, p: 4, c: 34, f: 1 },
  { n: "Lumpia (fried)", s: "2 pieces (100g)", cal: 230, p: 8, c: 22, f: 13 },
  { n: "Tokwa't Baboy", s: "1 serving (150g)", cal: 280, p: 18, c: 4, f: 20 },
  { n: "Pinakbet", s: "1 serving (150g)", cal: 160, p: 8, c: 15, f: 8 },
  { n: "Bistek", s: "1 serving (150g)", cal: 290, p: 28, c: 6, f: 17 },
  { n: "Tapsilog", s: "1 plate (250g)", cal: 520, p: 28, c: 45, f: 22 },
  { n: "Tosilog", s: "1 plate (250g)", cal: 540, p: 22, c: 50, f: 25 },
  { n: "Longsilog", s: "1 plate (250g)", cal: 560, p: 24, c: 48, f: 26 },
];

const MT_DB_NORM = MT_DB.map((i) => ({ ...i, key: i.n.toLowerCase() }));

let mtCurrentBase = null,
  mtCurrentMatches = [],
  mtActiveIdx = -1;
const MT_DAYS = [
  "Sunday",
  "Monday",
  "Tuesday",
  "Wednesday",
  "Thursday",
  "Friday",
  "Saturday",
];
const MT_MEALS = ["Breakfast", "Lunch", "Snack", "Dinner"];
const mtTodayIdx = new Date().getDay();
const mtTodayName = MT_DAYS[mtTodayIdx];

const mtStore = {};
MT_DAYS.forEach((d) => {
  mtStore[d] = {};
  MT_MEALS.forEach((m) => (mtStore[d][m] = []));
});

let mtFoodInput, mtSuggestDiv, mtPreview, mtStatusEl, mtAddBtn, mtQtyInput;

document.addEventListener("DOMContentLoaded", () => {
  mtFoodInput = document.getElementById("mtFoodName");
  mtSuggestDiv = document.getElementById("mtSuggestions");
  mtPreview = document.getElementById("mtNutritionPreview");
  mtStatusEl = document.getElementById("mtStatus");
  mtAddBtn = document.getElementById("mtAddBtn");
  mtQtyInput = document.getElementById("mtQtyInput");
  if (!mtFoodInput) return;

  mtQtyInput.addEventListener("input", () => {
    if (mtCurrentBase) mtUpdatePreview();
  });

  mtFoodInput.addEventListener("input", function () {
    const q = this.value.trim();
    mtActiveIdx = -1;
    if (q.length < 2) {
      mtHideSugg();
      mtHidePreview();
      mtStatusEl.textContent =
        "Type a food name — nutrition auto-fills based on quantity.";
      mtStatusEl.className = "";
      mtAddBtn.disabled = true;
      mtCurrentBase = null;
      return;
    }
    mtCurrentMatches = mtFuzzyMatch(q);
    if (mtCurrentMatches.length) {
      mtRenderSugg(mtCurrentMatches);
      mtSetBase(mtCurrentMatches[0]);
    } else {
      mtHideSugg();
      mtHidePreview();
      mtStatusEl.textContent = "No match found. Try a different name.";
      mtStatusEl.className = "warn";
      mtAddBtn.disabled = true;
      mtCurrentBase = null;
    }
  });

  mtFoodInput.addEventListener("keydown", function (e) {
    if (!mtCurrentMatches.length) return;
    const items = mtSuggestDiv.querySelectorAll(".mt-suggest-item");
    if (e.key === "ArrowDown") {
      e.preventDefault();
      mtActiveIdx = Math.min(mtActiveIdx + 1, items.length - 1);
      mtHighlightSugg(items);
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      mtActiveIdx = Math.max(mtActiveIdx - 1, 0);
      mtHighlightSugg(items);
    } else if (e.key === "Enter") {
      e.preventDefault();
      if (mtActiveIdx >= 0) mtSelectItem(mtCurrentMatches[mtActiveIdx]);
      else mtAddFood();
    } else if (e.key === "Escape") mtHideSugg();
  });

  document.addEventListener("click", (e) => {
    if (!e.target.closest("#mtSuggestWrap")) mtHideSugg();
  });
  mtRenderAll();
});

function mtFuzzyMatch(q) {
  const query = q.toLowerCase().trim();
  if (!query) return [];
  return MT_DB_NORM.map((item) => {
    let score = 0;
    if (item.key === query) score = 100;
    else if (item.key.startsWith(query)) score = 80;
    else if (item.key.includes(query)) score = 60;
    else {
      const w = query.split(" ").filter((x) => x.length > 1);
      const m = w.filter((x) => item.key.includes(x));
      if (m.length) score = m.length * 20;
    }
    return { ...item, score };
  })
    .filter((i) => i.score > 0)
    .sort((a, b) => b.score - a.score)
    .slice(0, 8);
}

function mtRenderSugg(matches) {
  mtSuggestDiv.style.display = "block";
  mtSuggestDiv.innerHTML = matches
    .map(
      (item, i) =>
        `<div class="mt-suggest-item" data-idx="${i}"><span>${item.n}</span><span class="mt-suggest-macros">${item.cal} cal · P${item.p} C${item.c} F${item.f}</span></div>`,
    )
    .join("");
  mtSuggestDiv.querySelectorAll(".mt-suggest-item").forEach((el, i) =>
    el.addEventListener("mousedown", (e) => {
      e.preventDefault();
      mtSelectItem(mtCurrentMatches[i]);
    }),
  );
}

function mtHighlightSugg(items) {
  items.forEach((el, i) => el.classList.toggle("active", i === mtActiveIdx));
  if (mtActiveIdx >= 0) mtSetBase(mtCurrentMatches[mtActiveIdx]);
}
function mtHideSugg() {
  mtSuggestDiv.style.display = "none";
}
function mtHidePreview() {
  mtPreview.classList.remove("visible");
}
function mtSelectItem(item) {
  mtFoodInput.value = item.n;
  mtSetBase(item);
  mtHideSugg();
}
function mtSetBase(item) {
  mtCurrentBase = item;
  mtUpdatePreview();
  mtAddBtn.disabled = false;
}

function mtGetQty() {
  return Math.max(0.5, parseFloat(mtQtyInput.value) || 1);
}
function mtChangeQty(d) {
  let v = Math.round((mtGetQty() + d) * 2) / 2;
  if (v < 0.5) v = 0.5;
  mtQtyInput.value = v;
  if (mtCurrentBase) mtUpdatePreview();
}

function mtUpdatePreview() {
  if (!mtCurrentBase) return;
  const qty = mtGetQty(),
    cal = Math.round(mtCurrentBase.cal * qty),
    p = Math.round(mtCurrentBase.p * qty),
    c = Math.round(mtCurrentBase.c * qty),
    f = Math.round(mtCurrentBase.f * qty);
  document.getElementById("mtPvCal").textContent = cal;
  document.getElementById("mtPvP").textContent = p + "g";
  document.getElementById("mtPvC").textContent = c + "g";
  document.getElementById("mtPvF").textContent = f + "g";
  document.getElementById("mtPvServing").innerHTML =
    `<span>${qty === 1 ? "1 serving" : qty + " servings"}</span> of ${mtCurrentBase.s} each`;
  mtPreview.classList.add("visible");
  mtStatusEl.textContent = `✓ ${mtCurrentBase.n} × ${qty} — press Add Food to log.`;
  mtStatusEl.className = "match";
}

function mtAddFood() {
  if (!mtCurrentBase) return;
  const meal = document.getElementById("mtMealType").value;
  const qty = mtGetQty();
  mtStore[mtTodayName][meal].push({
    name: mtCurrentBase.n,
    qty,
    cal: Math.round(mtCurrentBase.cal * qty),
    p: Math.round(mtCurrentBase.p * qty),
    c: Math.round(mtCurrentBase.c * qty),
    f: Math.round(mtCurrentBase.f * qty),
  });
  mtFoodInput.value = "";
  mtQtyInput.value = 1;
  mtHidePreview();
  mtHideSugg();
  mtStatusEl.textContent =
    "Type a food name — nutrition auto-fills based on quantity.";
  mtStatusEl.className = "";
  mtAddBtn.disabled = true;
  mtCurrentBase = null;
  mtCurrentMatches = [];
  mtActiveIdx = -1;
  mtRenderAll();
  const t = document.getElementById("mtToast");
  t.style.display = "block";
  setTimeout(() => (t.style.display = "none"), 1800);
}

function mtRemoveFood(meal, idx) {
  mtStore[mtTodayName][meal].splice(idx, 1);
  mtRenderAll();
}
function mtClearToday() {
  MT_MEALS.forEach((m) => (mtStore[mtTodayName][m] = []));
  mtRenderAll();
}
function mtDayHasFood(day) {
  return MT_MEALS.some((m) => mtStore[day][m].length > 0);
}

function mtRenderTodayMeals() {
  document.getElementById("mtTodayMeals").innerHTML = MT_MEALS.map((m) => {
    const items = mtStore[mtTodayName][m],
      totalCal = items.reduce((s, i) => s + i.cal, 0);
    const rows = items.length
      ? items
          .map(
            (item, idx) =>
              `<div class="mt-food-item"><div><div><span class="mt-food-name">${item.name}</span> <span class="mt-food-qty">×${item.qty}</span></div><div class="mt-food-kcal">${item.cal} cal · P${item.p}g · C${item.c}g · F${item.f}g</div></div><span class="mt-remove" onclick="mtRemoveFood('${m}',${idx})">×</span></div>`,
          )
          .join("")
      : `<div class="mt-empty-meal">No food added</div>`;
    return `<div class="mt-meal-card"><div class="mt-card-label">${m}${totalCal ? `<span class="mt-card-cal">${totalCal} CAL</span>` : ""}</div>${rows}</div>`;
  }).join("");
}

function mtRenderWeek() {
  const grid = document.getElementById("mtWeekGrid");
  const ordered = [
    ...MT_DAYS.slice(mtTodayIdx),
    ...MT_DAYS.slice(0, mtTodayIdx),
  ];
  const active = ordered.filter((d) => mtDayHasFood(d));
  if (!active.length) {
    grid.innerHTML = `<div class="mt-empty-week"><p>📋<br>No meals logged yet.<br>Add food above and it will appear here.</p></div>`;
    return;
  }
  grid.innerHTML = `<div class="mt-days-grid">${active
    .map((day) => {
      const isToday = day === mtTodayName,
        dd = mtStore[day];
      let tCal = 0,
        tP = 0,
        tC = 0,
        tF = 0;
      MT_MEALS.forEach((m) =>
        dd[m].forEach((i) => {
          tCal += i.cal;
          tP += i.p;
          tC += i.c;
          tF += i.f;
        }),
      );
      const mealRows = MT_MEALS.map((m) => {
        const items = dd[m];
        if (!items.length) return "";
        const mCal = items.reduce((s, i) => s + i.cal, 0),
          mP = items.reduce((s, i) => s + i.p, 0),
          mC = items.reduce((s, i) => s + i.c, 0),
          mF = items.reduce((s, i) => s + i.f, 0);
        return `<div class="mt-week-meal-row"><div class="mt-week-meal-top"><span class="mt-week-meal-type">${m}</span><span class="mt-week-meal-cal">${mCal} cal</span></div><div class="mt-week-meal-desc">${items.map((i) => i.name + (i.qty !== 1 ? ` ×${i.qty}` : "")).join(", ")}</div><div class="mt-week-meal-macros">P ${mP}g · C ${mC}g · F ${mF}g</div></div>`;
      })
        .filter(Boolean)
        .join("");
      return `<div class="mt-day-card${isToday ? " mt-today" : ""}"><div class="mt-day-hdr"><div class="mt-day-name">${day}${isToday ? '<span class="mt-today-badge">TODAY</span>' : ""}</div><span class="mt-day-cal">${tCal} cal</span></div><div class="mt-day-macros"><span class="mt-macro-pill">P ${tP}g</span><span class="mt-macro-pill">C ${tC}g</span><span class="mt-macro-pill">F ${tF}g</span></div>${mealRows || '<div class="mt-no-food">No meals logged</div>'}</div>`;
    })
    .join("")}</div>`;
}

function mtRenderAll() {
  mtRenderTodayMeals();
  mtRenderWeek();
}
