// assets/js/dashboard.js

export function initDashboardChart() {
  const el = document.getElementById('macroChart');
  if (!el) return;

  const ctx = el.getContext('2d');

  const calsNow  = parseFloat(el.dataset.calories || '0');
  const calsGoal = parseFloat(el.dataset.caloriesGoal || '2000');
  const proteinG = parseFloat(el.dataset.protein || '0');
  const carbsG   = parseFloat(el.dataset.carbs || '0');
  const fatG     = parseFloat(el.dataset.fat || '0');

  const proteinKcal = proteinG * 4;
  const carbsKcal   = carbsG * 4;
  const fatKcal     = fatG * 9;
  const eatenKcal   = proteinKcal + carbsKcal + fatKcal;

  let remainingKcal = calsGoal - eatenKcal;
  if (remainingKcal < 0) remainingKcal = 0;

  // Create gradients function that uses current canvas size
  function createGradients() {
    const width = el.width || 210;
    const gProtein = ctx.createLinearGradient(0, 0, width, 0);
    gProtein.addColorStop(0,   '#5433FF');
    gProtein.addColorStop(0.5, '#20BDFF');
    gProtein.addColorStop(1,   '#6FB1FC');

    const gCarbs = ctx.createLinearGradient(0, 0, width, 0);
    gCarbs.addColorStop(0, '#f12711');
    gCarbs.addColorStop(1, '#f5af19');

    const gFat = ctx.createLinearGradient(0, 0, width, 0);
    gFat.addColorStop(0, '#bc4e9c');
    gFat.addColorStop(1, '#f80759');

    return [gProtein, gCarbs, gFat];
  }

  const chart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Protein', 'Carbs', 'Fat', 'Remaining'],
      datasets: [{
        data: [proteinKcal, carbsKcal, fatKcal, remainingKcal],
        backgroundColor: createGradients().concat(['#eeeeee']),
        borderWidth: 0
      }]
    },
    options: {
      cutout: '70%',
      rotation: -90 * (Math.PI / 180),
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: 1,
      resizeDelay: 100,
      onResize: (chart, size) => {
        // Update gradients when resized
        const gradients = createGradients();
        chart.data.datasets[0].backgroundColor = gradients.concat(['#eeeeee']);
        chart.update('none');
      },
      plugins: { 
        legend: { display: false },
        tooltip: { enabled: true }
      }
    }
  });

  // Handle window resize
  let resizeTimer;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      chart.resize();
    }, 150);
  });
}

document.addEventListener('DOMContentLoaded', initDashboardChart);

// Searchable food dropdown
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('food-search');
  const dropdown = document.getElementById('food-dropdown');
  const foodIdHidden = document.getElementById('food-id-hidden');
  
  if (!searchInput || !dropdown) return;
  
  let options = dropdown.querySelectorAll('.dropdown-item');
  let highlightedIndex = -1;
  let filteredOptions = Array.from(options);
  let debounceTimer = null;

  function escapeHtml(str) {
    return String(str).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
  }

  async function fetchOptions(term) {
    const params = term ? `?search=${encodeURIComponent(term)}` : '';
    const res = await fetch(`/foods${params}`);
    if (!res.ok) return;
    const data = await res.json();
    const foods = Array.isArray(data.foods) ? data.foods : [];
    renderOptions(foods);
  }

  function renderOptions(foods) {
    dropdown.innerHTML = foods.map(f => `
      <div data-id="${String(f.id)}" class="dropdown-item" role="option" style="cursor:pointer; padding:0.5rem 1rem;">
        ${escapeHtml(f.name)}
      </div>
    `).join('');

    options = dropdown.querySelectorAll('.dropdown-item');
    filteredOptions = Array.from(options);
    highlightedIndex = -1;

    options.forEach(option => {
      option.addEventListener('click', function(e) {
        e.preventDefault();
        selectOption(this);
      });
    });
  }

  // Update highlighted item
  function updateHighlight() {
    options.forEach(opt => opt.classList.remove('highlighted'));
    if (highlightedIndex >= 0 && highlightedIndex < filteredOptions.length) {
      filteredOptions[highlightedIndex].classList.add('highlighted');
      const highlightedEl = filteredOptions[highlightedIndex];
      highlightedEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
  }

  // Select an option
  function selectOption(option) {
    const foodName = option.textContent.trim();
    const foodId = option.getAttribute('data-id');
    
    searchInput.value = foodName;
    if (foodIdHidden) foodIdHidden.value = foodId;
    dropdown.style.display = 'none';
    searchInput.setAttribute('aria-expanded', 'false');
  }

  // Show dropdown
  function showDropdown() {
    if (!dropdown.style.display || dropdown.style.display === 'none') {
      fetchOptions(searchInput.value);
    }
    dropdown.style.display = 'block';
    searchInput.setAttribute('aria-expanded', 'true');
  }

  // Hide dropdown
  function hideDropdown() {
    setTimeout(() => {
      dropdown.style.display = 'none';
      searchInput.setAttribute('aria-expanded', 'false');
      highlightedIndex = -1;
    }, 200);
  }

  // Search input events
  searchInput.addEventListener('focus', showDropdown);
  searchInput.addEventListener('input', function() {
    showDropdown();
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      fetchOptions(searchInput.value);
    }, 200);
  });
  searchInput.addEventListener('blur', hideDropdown);

  // Keyboard navigation
  searchInput.addEventListener('keydown', function(e) {
    if (!dropdown.style.display || dropdown.style.display === 'none') {
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') {
        showDropdown();
      }
      return;
    }

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      highlightedIndex = Math.min(highlightedIndex + 1, filteredOptions.length - 1);
      updateHighlight();
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      highlightedIndex = Math.max(highlightedIndex - 1, -1);
      updateHighlight();
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (highlightedIndex >= 0 && highlightedIndex < filteredOptions.length) {
        selectOption(filteredOptions[highlightedIndex]);
      } else if (filteredOptions.length === 1) {
        selectOption(filteredOptions[0]);
      } else if (filteredOptions.length > 0 && searchInput.value.toLowerCase() === filteredOptions[0].textContent.toLowerCase()) {
        selectOption(filteredOptions[0]);
      }
    } else if (e.key === 'Escape') {
      hideDropdown();
    }
  });

  // Option click events
  options.forEach(option => {
    option.addEventListener('click', function(e) {
      e.preventDefault();
      selectOption(this);
    });
  });

  // Close dropdown when clicking outside
  document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
      hideDropdown();
    }
  });

  // Prevent submit without a selected id
  if (searchInput && searchInput.form) {
    searchInput.form.addEventListener('submit', (e) => {
      if (!foodIdHidden.value) {
        e.preventDefault();
        showDropdown();
        searchInput.focus();
      }
    });
  }
});

  