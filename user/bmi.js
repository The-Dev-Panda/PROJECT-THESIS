function redirectToLogin(message) {
  window.location.href = "../Login/Login_Page.php" + (message ? "?error=" + encodeURIComponent(message) : "");
}

function handleApiResponse(response) {
  if (response.status === 401) {
    redirectToLogin("Session expired. Please log in.");
    return Promise.reject(new Error("Unauthorized"));
  }
  return response.json().then((body) => {
    if (!body.success && body.error && body.error.toLowerCase().includes("unauthorized")) {
      redirectToLogin(body.error);
      return Promise.reject(new Error(body.error));
    }
    return body;
  });
}

function loadProfileFromDb() {
  const endpoint = "../Database/get_member_profile.php";

  return fetch(endpoint)
    .then(handleApiResponse)
    .then((data) => {
      if (!data.success || !data.profile) {
        return null;
      }

      const profile = data.profile;
      if (!profile.height_cm || !profile.weight_kg) {
        return null;
      }

      return {
        height: Number(profile.height_cm),
        weight: Number(profile.weight_kg),
      };
    })
    .catch(() => null);
}

function saveProfileToDb(d) {
  return fetch("../Database/upsert_member_profile.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      height_cm: d.height,
      weight_kg: d.weight,
    }),
  }).then(handleApiResponse);
}

/* ── On load: restore saved BMI ── */
window.addEventListener("DOMContentLoaded", () => {
  const heightSlider = document.getElementById("mHeightSlider");
  const weightSlider = document.getElementById("mWeightSlider");

  mSync(heightSlider, "mHeightVal", "cm");
  mSync(weightSlider, "mWeightVal", "kg");

  loadProfileFromDb().then((dbProfile) => {
    if (dbProfile) {
      heightSlider.value = dbProfile.height;
      weightSlider.value = dbProfile.weight;
      mSync(heightSlider, "mHeightVal", "cm");
      mSync(weightSlider, "mWeightVal", "kg");
    }

    mCalculate(false);
  });
});

function applyDataToDashboard(d) {
  document.getElementById("dashBmiValue").textContent = d.bmi;
  document.getElementById("dashHeight").textContent = d.height + " cm";
  document.getElementById("dashWeight").textContent = d.weight + " kg";
  document.getElementById("dashToGoal").textContent = d.toGoal;
  document.getElementById("dashMarker").style.left = d.markerPct + "%";
  const badge = document.getElementById("dashBadge");
  badge.textContent = d.label;
  badge.className = "bmi-badge " + d.badgeClass;
}

/* ── Modal ── */
function openBMIModal() {
  document.getElementById("bmiModalOverlay").classList.add("open");
  document.body.style.overflow = "hidden";
}
function closeBMIModal() {
  document.getElementById("bmiModalOverlay").classList.remove("open");
  document.body.style.overflow = "";
}
function handleOverlayClick(e) {
  if (e.target === document.getElementById("bmiModalOverlay")) closeBMIModal();
}
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") closeBMIModal();
});

/* ── Sliders ── */
function mPct(el) {
  el.style.setProperty(
    "--pct",
    ((el.value - el.min) / (el.max - el.min)) * 100 + "%",
  );
}
function mSync(el, valId, unit) {
  document.getElementById(valId).childNodes[0].textContent = el.value + " ";
  mPct(el);
}

let lastCalcData = null;

/* ── Calculate ── */
function mCalculate(shouldPersist = true) {
  const hRaw = parseFloat(document.getElementById("mHeightSlider").value);
  const wRaw = parseFloat(document.getElementById("mWeightSlider").value);
  const hM = hRaw / 100;
  const bmi = wRaw / (hM * hM);
  const b = Math.round(bmi * 10) / 10;

  let label, color, tip, badgeClass;
  if (bmi < 18.5) {
    label = "Underweight";
    color = "#4da6ff";
    badgeClass = "underweight";
    tip =
      "Your BMI is below the healthy range. Focus on nutrient-dense foods and progressive strength training. A caloric surplus of 300–500 kcal/day with high protein helps build lean mass safely.";
  } else if (bmi < 25) {
    label = "Healthy";
    color = "#00c875";
    badgeClass = "healthy";
    tip =
      "Your BMI is in the healthy range — great work! Stay consistent with regular training and balanced nutrition. Focus on strength and cardiovascular endurance to stay at peak performance.";
  } else if (bmi < 30) {
    label = "Overweight";
    color = "#f5c518";
    badgeClass = "overweight";
    tip =
      "Your BMI is slightly elevated. A moderate caloric deficit of 300–500 kcal/day with 3–4 cardio sessions per week can help. Prioritize protein to preserve muscle while losing fat.";
  } else {
    label = "Obese";
    color = "#ff4d4d";
    badgeClass = "obese";
    tip =
      "Your BMI indicates a higher health risk. Speak with a healthcare professional for a personalized plan. Consistent daily walks and gradual dietary changes compound fast.";
  }

  const minW = 18.5 * hM * hM,
    maxW = 24.9 * hM * hM,
    idealW = 21.7 * hM * hM,
    diff = wRaw - idealW;
  const fmt = (kg) => Math.round(kg) + " kg";
  const needlePct = Math.min(Math.max(((bmi - 10) / 30) * 100, 2), 97);

  document.getElementById("mBmiNum").textContent = b;
  document.getElementById("mRH").textContent = hRaw + " cm";
  document.getElementById("mRW").textContent = wRaw + " kg";
  document.getElementById("mRRange").textContent =
    fmt(minW) + " – " + fmt(maxW);
  document.getElementById("mRDiff").textContent =
    (diff >= 0 ? "+" : "") +
    fmt(diff) +
    (diff > 0 ? " over" : diff < 0 ? " under" : " ✓");
  document.getElementById("mTip").textContent = tip;
  document.getElementById("mNeedle").style.left = needlePct + "%";

  const badge = document.getElementById("mBadge");
  badge.textContent = label;
  badge.style.color = color;
  badge.style.borderColor = color;
  badge.style.background = color + "18";

  const res = document.getElementById("mResult");
  res.classList.remove("show");
  void res.offsetWidth;
  res.classList.add("show");

  lastCalcData = {
    bmi: b,
    height: hRaw,
    weight: wRaw,
    label,
    badgeClass,
    markerPct: needlePct,
    toGoal: (diff >= 0 ? "+" : "") + fmt(diff),
  };

  applyDataToDashboard(lastCalcData);
  if (shouldPersist) {
    saveProfileToDb({ height: hRaw, weight: wRaw }).catch(() => {});
  }
}

