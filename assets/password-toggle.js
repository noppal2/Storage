document.addEventListener('DOMContentLoaded', function () {
  const style = document.createElement('style');

  style.textContent = `
    .password-toggle {
      min-width: 46px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .password-toggle .eye-closed,
    .password-toggle .eye-open {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 1.15rem;
      height: 1.15rem;
      font-size: 0;
      line-height: 1;
    }

    .password-toggle .eye-closed svg,
    .password-toggle .eye-open svg {
      display: none !important;
    }

    .password-toggle .eye-closed::before,
    .password-toggle .eye-open::before {
      content: "";
      display: block;
      width: 1.15rem;
      height: 1.15rem;
      background: center/contain no-repeat;
    }

    .password-toggle .eye-closed::before {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='%2341628d' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M1 8s2.5-4 7-4 7 4 7 4-2.5 4-7 4-7-4-7-4Z'/%3E%3Ccircle cx='8' cy='8' r='2'/%3E%3C/svg%3E");
    }

    .password-toggle .eye-open::before {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='%2341628d' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M1 8s2.5-4 7-4 7 4 7 4-2.5 4-7 4-7-4-7-4Z'/%3E%3Ccircle cx='8' cy='8' r='2'/%3E%3Cpath d='m2 2 12 12'/%3E%3C/svg%3E");
    }

    .eye-open.d-none,
    .eye-closed.d-none {
      display: none !important;
    }
  `;

  document.head.appendChild(style);

  const iconMarkup = `
    <span class="eye-closed" aria-hidden="true">
      <svg class="password-eye-icon" viewBox="0 0 16 16">
        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.17 8a13 13 0 0 1 1.66-2.04C4.12 4.67 5.88 3.5 8 3.5s3.88 1.17 5.17 2.46A13 13 0 0 1 14.83 8a13 13 0 0 1-1.66 2.04C11.88 11.33 10.12 12.5 8 12.5s-3.88-1.17-5.17-2.46A13 13 0 0 1 1.17 8" />
        <path d="M8 5.5A2.5 2.5 0 1 0 8 10.5 2.5 2.5 0 0 0 8 5.5" />
      </svg>
    </span>
    <span class="eye-open d-none" aria-hidden="true">
      <svg class="password-eye-icon" viewBox="0 0 16 16">
        <path d="m13.36 11.24 2.49 2.49-.71.71-14-14-.71.71 2.23 2.23A9 9 0 0 1 8 1.5c5 0 8 5.5 8 5.5a14 14 0 0 1-2.64 4.24M5.8 3.68l1.02 1.02A2.5 2.5 0 0 1 10.3 8.18l1.8 1.8A12 12 0 0 0 14.83 7a13 13 0 0 0-1.66-2.04C11.88 3.67 10.12 2.5 8 2.5c-.78 0-1.52.16-2.2.42zM8.18 10.48l1.18 1.18c-.44.11-.9.17-2.1-3.17l.72.72A12 12 0 0 0 1.17 6.33a13 13 0 0 0 1.66 2.04C4.12 9.66 5.88 10.83 8 10.83z" />
      </svg>
    </span>
  `;

  document.querySelectorAll('input[type="password"]').forEach(function (input) {
    let group = input.closest('.input-group');
    let button = group?.querySelector('.password-toggle, [data-password-toggle], #toggle-login-password');

    if (!group) {
      group = document.createElement('div');
      group.className = 'input-group';
      input.parentNode.insertBefore(group, input);
      group.appendChild(input);
    }

    if (!button) {
      button = document.createElement('button');
      button.type = 'button';
      button.className = 'btn btn-outline-secondary password-toggle';
      group.appendChild(button);
    }

    button.classList.add('password-toggle');
    button.innerHTML = iconMarkup;
    button.setAttribute('aria-label', 'Tampilkan password');
    button.setAttribute('title', 'Tampilkan password');

    if (button.id === 'toggle-login-password' || button.dataset.passwordToggleBound) {
      return;
    }

    button.dataset.passwordToggleBound = '1';
    button.addEventListener('click', function () {
      const visible = input.type === 'password';

      input.type = visible ? 'text' : 'password';
      button.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
      button.setAttribute('title', visible ? 'Sembunyikan password' : 'Tampilkan password');
      button.querySelector('.eye-closed').classList.toggle('d-none', visible);
      button.querySelector('.eye-open').classList.toggle('d-none', !visible);
    });
  });
});
