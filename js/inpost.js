import Modal from './modal';

class ChocanteInpost {
  static INPOST_SELECT_BUTTON = 'chocanteSelectInpostParcelLocker';
  static INPOST_ADDRESS = 'chocanteInpostParcelLocker';
  static INPOST_MODAL = 'inpostModal'
  static AJAX_ACTION = 'chocante_delivery_point_save';
  static MISSING_INPOST_MODAL = 'Chocante Inpost: Missing InPost modal element';
  static BAD_REQUEST = 'Chocante Inpost: Bad request';
  static DEFAULT_LANGUAGE = 'pl';
  static DEFAULT_CONFIG = 'parcelCollect';
  static POINTS_API = 'https://api-shipx-pl.easypack24.net/v1/points'

  constructor() {
    this.ajaxUrl = chocante_inpost.ajaxurl;
    this.nonce = chocante_inpost.nonce;
    this.token = '';
    this.language = ChocanteInpost.DEFAULT_LANGUAGE;
    this.config = ChocanteInpost.DEFAULT_CONFIG;
    this.point = '';
    this.widget = null;

    // Listen for WooCommerce event
    document.addEventListener('checkoutUpdated', this.init.bind(this));

    // Listen for point selection from select.
    document.addEventListener('chocanteInpostPointSelected', this.onPointSelection.bind(this));
  }

  init() {
    const inpostModal = document.getElementById(ChocanteInpost.INPOST_MODAL);
    this.selectButton = document.getElementById(ChocanteInpost.INPOST_SELECT_BUTTON);

    if (!inpostModal) {
      throw new Error(ChocanteInpost.MISSING_INPOST_MODAL);
    }

    // Init only for selected shipping methods
    if (this.selectButton) {
      this.token = this.selectButton.dataset.token;
      this.language = this.selectButton.dataset.widgetLanguage;
      this.point = this.selectButton.dataset.point;
      this.config = this.selectButton.dataset.widgetConfig;

      // Remove widget element with old config
      this.removeOldWidget();

      // Append new widget element
      this.addWidget(inpostModal);

      this.inpostWidget = document.querySelector('inpost-geowidget');
      this.modal = new Modal(inpostModal);

      // FIX loading iframe multiplie iframes
      window.requestAnimationFrame(this.fixWidgetLoading.bind(this));

      this.inpostWidget.addEventListener('inpost.geowidget.init', this.initGeoWidget.bind(this));
      document.body.addEventListener('click', this.showModal.bind(this));
    }
  }

  initGeoWidget(event) {
    const api = event.detail.api;

    api.addPointSelectedCallback(this.afterPointSelected.bind(this));

    window.requestAnimationFrame(() => {
      api.changeLanguage(this.language);

      if (this.point !== '') {
        api.showPoint(this.point);
      }
    });

    this.selectButton.disabled = false;
    this.widget = api;
  }

  async afterPointSelected({ name, address: { line1, line2 }, location_description: locationDescription }) {
    const address = `${line1}, ${line2}${locationDescription !== '' ? `  - ${locationDescription}` : ''}`;
    const deliveryPointAddress = `${name} (${address})`;

    document.dispatchEvent(new CustomEvent('chocanteInpostPointSelectedOnMap', {
      detail: {
        name,
        deliveryPointAddress
      },
    }));

    this.modal.hide();

    await this.postSelectedPoint(name, address);
  }

  async postSelectedPoint(name, address) {
    try {
      const deliveryPoint = new FormData();

      deliveryPoint.append('action', ChocanteInpost.AJAX_ACTION);
      deliveryPoint.append('_ajax_nonce', this.nonce);
      deliveryPoint.append('number', name);
      deliveryPoint.append('address', address);

      await fetch(this.ajaxUrl, {
        method: 'POST',
        body: deliveryPoint
      });
    } catch (error) {
      throw new Error(ChocanteInpost.BAD_REQUEST);
    }
  }

  showModal(event) {
    if (event.target.id == ChocanteInpost.INPOST_SELECT_BUTTON) {
      event.preventDefault();

      this.modal.show();
    }
  }

  removeOldWidget() {
    const existingWidget = document.querySelector('inpost-geowidget');

    if (existingWidget) {
      existingWidget.remove();
    }
  }

  addWidget(inpostModal) {
    let widget = document.createElement('inpost-geowidget');

    widget.setAttribute('token', this.token);
    widget.setAttribute('language', this.language);
    widget.setAttribute('config', this.config);

    inpostModal.append(widget);
  }

  fixWidgetLoading() {
    let widgetIfrmames = this.inpostWidget.querySelectorAll('iframe');

    if (Array.from(widgetIfrmames).length > 1) {
      Array.from(widgetIfrmames).forEach((iframe, index) => {
        if (index > 0) {
          iframe.remove();
        }
      });
    }
  }

  async onPointSelection(event) {
    const { name, address } = event.detail;

    this.point = name;

    if (this.widget) {
      this.widget.showPoint(this.point);
    }

    await this.postSelectedPoint(name, address);
  }
}

new ChocanteInpost();

(function ($) {
  initSelect();

  $(document.body).on('updated_checkout', () => {
    document.dispatchEvent(new CustomEvent('checkoutUpdated'));

    initSelect();
  });

  function initSelect() {
    const select = $('#chocanteDeliveryPointInpost');
    let location247, paymentAvailable;

    switch (select.data('config')) {
      case 'parcelCollectPayment':
        paymentAvailable = true;
        break;
      case 'parcelCollect247':
        location247 = true;
        break;
    }


    const selectElement = select.selectWoo({
      minimumInputLength: 3,
      language: select.data('language'),
      ajax: {
        url: ChocanteInpost.POINTS_API,
        dataType: 'json',
        delay: 250,
        data: (params) => ({
          query: params.term,
          per_page: 10,
          fields: `name,address,location_description`,
          location247,
          paymentAvailable
        }),
        processResults: data => {
          let results = [];

          for (let { name, address: { line1, line2 }, location_description: locationDescription } of data.items) {
            const address = `${line1}, ${line2}${locationDescription !== '' ? `  - ${locationDescription}` : ''}`;
            results.push({
              id: name,
              text: `${name} (${address})`,
              address
            })
          }

          return {
            results
          };
        },
      },
    });

    selectElement.on('select2:select', (event) => {
      document.dispatchEvent(new CustomEvent('chocanteInpostPointSelected', {
        detail: {
          name: event.params.data.id,
          address: event.params.data.address
        },
      }));
    });

    $(document).on('chocanteInpostPointSelectedOnMap', (event) => {
      const { name, deliveryPointAddress } = event.originalEvent.detail;


      if (name && deliveryPointAddress) {
        const option = new Option(deliveryPointAddress, name, true, true);
        selectElement.append(option).trigger('change');
      }
    });

    const script = document.createElement('script');
    script.src = `https://cdn.jsdelivr.net/npm/select2/dist/js/i18n/${select.data('language')}.js`

    document.body.append(script);
  }
})(jQuery);

