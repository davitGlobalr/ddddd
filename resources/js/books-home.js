function clampInt(value, min, max) {
  const intVal = Number.parseInt(String(value ?? ''), 10);
  if (Number.isNaN(intVal)) return min;
  return Math.max(min, Math.min(max, intVal));
}

document.addEventListener('click', (event) => {
  const button = event.target.closest('.btn-qty-plus, .btn-qty-minus');
  if (!button) return;

  const targetId = button.dataset.qtyTarget;
  if (!targetId) return;

  const input = document.getElementById(targetId);
  if (!input || input.disabled) return;

  const max = clampInt(input.dataset.qtyMax, 0, Number.MAX_SAFE_INTEGER);
  const current = clampInt(input.value, 1, max || 1);

  const next =
    button.classList.contains('btn-qty-plus')
      ? Math.min(max, current + 1)
      : Math.max(1, current - 1);

  input.value = String(next);
});

document.addEventListener('input', (event) => {
  const input = event.target.closest?.('.qty-input');
  if (!input) return;
  if (input.disabled) return;

  const max = clampInt(input.dataset.qtyMax, 0, Number.MAX_SAFE_INTEGER);
  const value = clampInt(input.value, 1, max);
  input.value = String(value);
});

document.addEventListener('submit', (event) => {
  const form = event.target.closest?.('form.booking-form');
  if (!form) return;

  const submitButton = form.querySelector('button[type="submit"]');
  if (submitButton) {
    submitButton.disabled = true;
  }
});

