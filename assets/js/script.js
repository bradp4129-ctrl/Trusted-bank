document.addEventListener('DOMContentLoaded', function () {
  const year = document.querySelector('#current-year');
  if (year) {
    year.textContent = new Date().getFullYear();
  }

  const loader = document.createElement('div');
  loader.id = 'page-loader';
  loader.innerHTML = `
    <div class="loader-content">
      <div class="loader-ring"></div>
      <div class="loader-text">Loading</div>
    </div>
  `;
  document.body.appendChild(loader);

  let timeoutId = null;

  function showLoader(message = 'Loading', callback) {
    loader.classList.add('visible');
    const textEl = loader.querySelector('.loader-text');
    if (textEl) {
      textEl.textContent = message;
    }

    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
      if (typeof callback === 'function') {
        callback();
      }
    }, 4000);
  }

  function handleLinkClick(event) {
    const anchor = event.target.closest('a[href]');
    if (!anchor) {
      return;
    }
    const href = anchor.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) {
      return;
    }
    if (anchor.target === '_blank') {
      return;
    }
    if (href.startsWith('http') && !href.startsWith(window.location.origin)) {
      return;
    }
    event.preventDefault();
    showLoader('Loading', () => {
      window.location.href = href;
    });
  }

  document.addEventListener('click', handleLinkClick);

  document.querySelectorAll('form').forEach((form) => {
    if (form.matches('[data-skip-global-submit]')) {
      return;
    }
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      showLoader('Submitting', () => {
        form.submit();
      });
    });
  });

  const transactionTypeSelect = document.querySelector('select[name="type"]');
  const recipientField = document.querySelector('#recipient-field');
  const transferPinNote = document.querySelector('#transfer-pin-note');
  const transactionForm = document.querySelector('form[data-transaction-form]');
  const transactionSubmitButton = document.querySelector('#transaction-submit-button');
  const transferPinModal = document.querySelector('#transfer-pin-modal');
  const transferPinForm = document.querySelector('#transfer-pin-form');
  const transferPinInput = document.querySelector('#transfer-pin-input');
  const transferPinHidden = document.querySelector('#transfer-pin-hidden');
  const cancelTransferPinButton = document.querySelector('#cancel-transfer-pin');

  if (transactionForm && transactionTypeSelect && recipientField && transferPinModal && transferPinForm && transferPinInput && transferPinHidden && transactionSubmitButton) {
    const toggleRecipientField = () => {
      const isTransfer = transactionTypeSelect.value === 'transfer';
      recipientField.style.display = isTransfer ? 'grid' : 'none';
      if (transferPinNote) {
        transferPinNote.style.display = isTransfer ? 'block' : 'none';
      }
      const recipientInput = recipientField.querySelector('input[name="recipient_email"]');
      if (recipientInput) {
        recipientInput.required = isTransfer;
      }
    };

    toggleRecipientField();

    transactionTypeSelect.addEventListener('change', function () {
      toggleRecipientField();
    });

    transactionSubmitButton.addEventListener('click', () => {
      if (transactionTypeSelect.value === 'transfer') {
        transferPinModal.style.display = 'flex';
        transferPinInput.value = '';
        setTimeout(() => transferPinInput.focus(), 10);
        return;
      }
      transactionForm.submit();
    });

    cancelTransferPinButton.addEventListener('click', () => {
      transferPinModal.style.display = 'none';
    });

    transferPinForm.addEventListener('submit', (event) => {
      event.preventDefault();
      if (!/^[0-9]{4}$/.test(transferPinInput.value.trim())) {
        transferPinInput.setCustomValidity('Enter a valid 4-digit PIN.');
        transferPinInput.reportValidity();
        return;
      }
      transferPinHidden.value = transferPinInput.value.trim();
      transferPinModal.style.display = 'none';
      transactionForm.submit();
    });

    transferPinModal.addEventListener('click', (event) => {
      if (event.target === transferPinModal) {
        transferPinModal.style.display = 'none';
      }
    });

  }

  const balanceToggleButton = document.getElementById('toggleBalanceBtn');
  const balanceValue = document.getElementById('balanceValue');

  if (balanceToggleButton && balanceValue) {
    const originalAmount = balanceValue.dataset.amount || balanceValue.textContent;
    const eyeOpenIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4.5C7 4.5 2.7 7.6 1 12c1.7 4.4 6 7.5 11 7.5s9.3-3.1 11-7.5C21.3 7.6 17 4.5 12 4.5zm0 12a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9zm0-7.5a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>';
    const eyeClosedIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 4.5l1.4-1.4L21 20.7 19.6 22 16.2 18.6C14.5 19.3 12.8 19.5 11 19.5 6.5 19.5 2.7 16.4 1 12c.7-1.7 1.8-3.2 3.2-4.4L2 4.5zm10 10.5c2 0 3.7-.6 5.1-1.7l-1.5-1.5A4.5 4.5 0 0 1 7.5 12c0-.8.2-1.6.5-2.2L6 9.8A6.3 6.3 0 0 0 4.5 12c1.4 3.4 4.9 5.5 8.5 5.5zm-8.8-8.8L4.5 7.8C5.8 5.9 8.6 4.5 12 4.5c1.5 0 2.9.3 4.1.9L14 6.9A4.5 4.5 0 0 0 7.5 12c0 .6.1 1.2.3 1.8L3.2 6.2z"/></svg>';

    const updateBalanceIcon = (hidden) => {
      balanceToggleButton.innerHTML = hidden ? eyeClosedIcon : eyeOpenIcon;
      balanceToggleButton.setAttribute('aria-label', hidden ? 'Show balance' : 'Hide balance');
    };

    updateBalanceIcon(false);

    balanceToggleButton.addEventListener('click', () => {
      const hidden = balanceValue.classList.toggle('balance-hidden');
      balanceValue.textContent = hidden ? '••••••' : originalAmount;
      updateBalanceIcon(hidden);
    });
  }

  const transactionResult = document.querySelector('#transaction-result');
  if (transactionResult) {
    transactionResult.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
});

