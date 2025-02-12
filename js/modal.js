export default class Modal {
  static MODAL_TRANSITION = 'transition';
  static MODAL_OPEN = 'show';
  static MODAL_BACKDROP = 'modalBackdrop';
  static MODAL_CLOSE = 'modalClose';

  constructor(element) {
    this.modal = element;
    this.modalBackdrop = document.getElementById(Modal.MODAL_BACKDROP);
    this.modalClose = this.modal.querySelector(`#${Modal.MODAL_CLOSE}`);

    if (!this.modalBackdrop) {
      let modalBackdrop = document.createElement('div');
      modalBackdrop.id = Modal.MODAL_BACKDROP;

      this.modal.parentNode.append(modalBackdrop, this.modal);
      this.modalBackdrop = modalBackdrop;
    }

    if (this.modalClose) {
      this.modalClose.addEventListener('click', this.hide.bind(this));
    }

    this.removeTransitiondHandler = this.removeTransition.bind(this);
  }

  show() {
    this.modal.classList.add(Modal.MODAL_TRANSITION);
    this.modal.classList.add(Modal.MODAL_OPEN);

    this.modalBackdrop.classList.add(Modal.MODAL_TRANSITION);
    this.modalBackdrop.classList.add(Modal.MODAL_OPEN);

    this.modalBackdrop.addEventListener('click', this.hide.bind(this));
  }

  hide() {
    this.modal.classList.remove(Modal.MODAL_OPEN);
    this.modalBackdrop.classList.remove(Modal.MODAL_OPEN);
    this.modalBackdrop.addEventListener('transitionend', this.removeTransitiondHandler);
  }

  removeTransition() {
    this.modal.classList.remove(Modal.MODAL_TRANSITION);
    this.modalBackdrop.classList.remove(Modal.MODAL_TRANSITION);

    this.modalBackdrop.removeEventListener('transitionend', this.removeTransitiondHandler);
  }
}
