    </div>

    <script>
        document.querySelectorAll('.eye-icon').forEach(icon => {
            icon.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const field = document.getElementById(targetId);
                const i = this.querySelector('i');
                
                if (field.type === 'password') {
                    field.type = 'text';
                    if (i) i.className = 'far fa-eye-slash';
                    else this.textContent = 'Hide';
                } else {
                    field.type = 'password';
                    if (i) i.className = 'far fa-eye';
                    else this.textContent = 'Show';
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.card');
            setTimeout(function() {
                card.style.transition = 'opacity 0.5s, transform 0.5s';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>