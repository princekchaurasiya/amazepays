<script>
    function verifyemail() {
        var email = $('#email').val();
        if (email == '') {
            alert('Please enter email');
            return false;
        }
        $.ajax({
            url: "{{ route('verify.email') }}",
            type: "POST",
            data: {
                email: email,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response.status === 'success') {
                    alert(response.message);
                } else {
                    alert(response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error: ", status, error);
                alert('An error occurred while verifying the email. Please try again.');
            }
        });
    }

    $(document).ready(function () {
        $(".navbar-toggler").click(function (event) {
            event.preventDefault();
            $(".navbar").toggleClass("collapsed");
            var sidenav = $(".sidenav");
            if (sidenav.width() === 0) {
                sidenav.css("width", "250px");
            } else {
                sidenav.css("width", "0");
            }
        });
    });

    $(document).ready(function () {
        $('.amazepay-sidebar-toggle').click(function () {
            $('.amazepay-sidebar').addClass('active');
            $('.amazepay-overlay').addClass('active');
        });
        $('.amazepay-sidebar-close, .amazepay-overlay').click(function () {
            $('.amazepay-sidebar').removeClass('active');
            $('.amazepay-overlay').removeClass('active');
        });
    });

    $(document).ready(function () {
        $('.modal').on('shown.bs.modal', function () {
            $(this).removeAttr('aria-hidden').removeAttr('inert');
        });
        $('.modal').on('hidden.bs.modal', function () {
            $(this).attr('aria-hidden', 'true').attr('inert', '');
            $('body').focus();
        });
    });

    function closeNav() {
        var sidenav = document.querySelector(".sidenav");
        var navbar = document.querySelector(".navbar");
        navbar.classList.add("collapsed");
        sidenav.style.width = "0";
    }
</script>
