// assets/js/app.js
document.addEventListener('DOMContentLoaded', function(){
  // seat click handling (movie.php)
  const seats = document.querySelectorAll('.seat.available');
  const selectedInput = document.getElementById('selectedSeatsInput');
  if(seats.length && selectedInput){
    seats.forEach(s => {
      s.addEventListener('click', () => {
        if(s.classList.contains('selected')) s.classList.remove('selected');
        else s.classList.add('selected');
        const selected = Array.from(document.querySelectorAll('.seat.selected')).map(x=>x.dataset.label);
        selectedInput.value = selected.join(',');
        const priceElement = document.getElementById('price');
        const price = priceElement ? parseFloat(priceElement.innerText || 0) : 0;
        const totalAmountElement = document.getElementById('totalAmount');
        if (totalAmountElement) {
            totalAmountElement.innerText = (selected.length * price).toFixed(2);
        }
      });
    });
  }

  // simple showtime change reload
  const showtimeSelect = document.getElementById('showtimeSelect');
  if(showtimeSelect){
    showtimeSelect.addEventListener('change', ()=> {
      const sid = showtimeSelect.value;
      const url = new URL(window.location.href);
      url.searchParams.set('showtime', sid);
      window.location = url.toString();
    });
  }

  // --- Live Search Logic ---
  const searchInput = document.getElementById('searchInput');
  const genreFilter = document.getElementById('genreFilter');
  const languageFilter = document.getElementById('languageFilter');
  const resultsContainer = document.getElementById('movieResults');

  if (searchInput && resultsContainer) {
    const performSearch = () => {
        const query = searchInput.value;
        const genre = genreFilter ? genreFilter.value : '';
        const language = languageFilter ? languageFilter.value : '';

        const url = new URL(window.location.origin + window.location.pathname);
        url.searchParams.set('ajax', '1');
        if (query) url.searchParams.set('search', query);
        if (genre) url.searchParams.set('genre', genre);
        if (language) url.searchParams.set('language', language);

        // Update URL without reloading for better UX
        const displayUrl = new URL(window.location.href);
        displayUrl.searchParams.set('search', query);
        displayUrl.searchParams.set('genre', genre);
        displayUrl.searchParams.set('language', language);
        if (!query) displayUrl.searchParams.delete('search');
        if (!genre) displayUrl.searchParams.delete('genre');
        if (!language) displayUrl.searchParams.delete('language');
        window.history.replaceState({}, '', displayUrl);

        fetch(url)
            .then(res => res.text())
            .then(html => {
                resultsContainer.innerHTML = html;
            })
            .catch(err => console.error('Search error:', err));
    };

    const debounce = (func, wait) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    };

    const debouncedSearch = debounce(performSearch, 300);

    searchInput.addEventListener('input', debouncedSearch);
    if (genreFilter) genreFilter.addEventListener('change', performSearch);
    if (languageFilter) languageFilter.addEventListener('change', performSearch);
  }
});
