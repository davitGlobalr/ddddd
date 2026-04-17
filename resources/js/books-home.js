function clampInt(value, min, max) {
  const intVal = Number.parseInt(String(value ?? ''), 10);
  if (Number.isNaN(intVal)) return min;
  return Math.max(min, Math.min(max, intVal));
}

function showBookingAlert(type, text) {
  const root = $('#booking-ajax-alert');
  if (root.length === 0) return;

  root.html(
    `<div class="alert alert-${type} mb-4 booking-ajax-message" role="alert">${$('<div>').text(text).html()}</div>`
  );

  hideBookingAlertLater();
}

function hideBookingAlertLater() {
  const root = $('#booking-ajax-alert');
  if (root.length === 0) return;

  setTimeout(function () {
    root.find('.alert').fadeOut(300, function () {
      $(this).remove();
    });
  }, 5000);
}

$(function () {
  hideBookingAlertLater();

  $(document).on('click', '.btn-qty-plus, .btn-qty-minus', function () {
    const button = $(this);
    const targetId = button.data('qty-target');
    if (!targetId) return;

    const input = $(`#${targetId}`);
    if (input.length === 0 || input.prop('disabled')) return;

    const max = clampInt(input.data('qty-max'), 0, Number.MAX_SAFE_INTEGER);
    const current = clampInt(input.val(), 1, max || 1);

    const next = button.hasClass('btn-qty-plus')
      ? Math.min(max, current + 1)
      : Math.max(1, current - 1);

    input.val(String(next));
  });

  $(document).on('input', '.qty-input', function () {
    const input = $(this);
    if (input.prop('disabled')) return;

    const max = clampInt(input.data('qty-max'), 0, Number.MAX_SAFE_INTEGER);
    const value = clampInt(input.val(), 1, max);
    input.val(String(value));
  });

  $(document).on('submit', 'form.booking-form', function (event) {
    event.preventDefault();

    const form = $(this);
    const submitButton = form.find('button[type="submit"]');
    const quantityInput = form.find('.qty-input');
    const stockBadge = form.closest('.card-body').find('.js-stock-badge');

    submitButton.prop('disabled', true);

    $.ajax({
      url: form.attr('action'),
      method: 'POST',
      data: form.serialize(),
      dataType: 'json',
      headers: {
        Accept: 'application/json',
      },
    })
      .done(function (response) {
        const message = response?.message || 'Booking created successfully.';
        showBookingAlert('success', message);

        const remaining = clampInt(response?.remaining_quantity, 0, Number.MAX_SAFE_INTEGER);
        stockBadge.text(`В наличии: ${remaining}`);

        quantityInput
          .attr('max', String(remaining))
          .data('qty-max', remaining)
          .prop('disabled', remaining <= 0)
          .val(remaining > 0 ? '1' : '0');

        form.find('.btn-qty-plus, .btn-qty-minus').prop('disabled', remaining <= 0);
        submitButton.prop('disabled', true);
      })
      .fail(function (xhr) {
        const errors = xhr?.responseJSON?.errors;
        let message = xhr?.responseJSON?.message || 'Booking failed.';

        if (errors) {
          const firstKey = Object.keys(errors)[0];
          if (firstKey && Array.isArray(errors[firstKey]) && errors[firstKey].length > 0) {
            message = errors[firstKey][0];
          }
        }

        showBookingAlert('danger', message);
        submitButton.prop('disabled', false);
      });
  });
});

