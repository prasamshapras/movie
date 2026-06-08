// js/app.js
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
        const price = parseFloat(document.getElementById('price').innerText||0);
        document.getElementById('totalAmount').innerText = (selected.length * price).toFixed(2);
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
});
