import Indexer from './indexer';

const { __, sprintf } = wp.i18n;

class ToolsApp {
	constructor(settings) {
		this.settings = settings;
	}

	enable() {
		this.indexer = new Indexer();
		this.state = { status: 'settings', message: '' };

		this.onIndex('loadStart', 'didLoadStart');
		this.onIndex('loadComplete', 'didLoadComplete');
		this.onIndex('loadError', 'didLoadError');
		this.onIndex('indexStart', 'didIndexStart');
		this.onIndex('indexProgress', 'didIndexProgress');
		this.onIndex('indexComplete', 'didIndexComplete');
		this.onIndex('indexCancel', 'didIndexCancel');
		this.onIndex('indexError', 'didIndexError');

		this.onIndex('deleteIndexStart', 'didDeleteIndexStart');
		this.onIndex('deleteIndexComplete', 'didDeleteIndexComplete');
		this.onIndex('deleteIndexError', 'didDeleteIndexError');
		this.onIndex('deleteIndexCancel', 'didDeleteIndexCancel');

		this.on('.block-catalog-post-type', 'change', 'didPostTypesChange');

		this.on('#submit', 'click', 'didSubmitClick');
		this.on('#cancel', 'click', 'didCancelClick');

		this.on('#delete-index', 'click', 'didDeleteIndexClick');
		this.on('#cancel-delete', 'click', 'didDeleteCancelClick');
	}

	setState(state) {
		this.prevState = this.state;
		this.state = state;

		switch (state.status) {
			case 'loading':
				this.hide('#index-settings');
				this.show('#index-status');
				this.updateProgress();
				break;

			case 'loaded':
				this.hide('#index-settings');
				this.show('#index-status');
				break;

			case 'load-error':
				this.show('#index-settings');
				this.hide('#index-status');
				break;

			case 'settings':
				this.show('#index-settings');
				this.hide('#index-status');
				break;

			case 'indexing':
				this.hide('#index-settings');
				this.show('#index-status');
				this.updateProgress();
				break;

			case 'indexed':
				this.hide('#index-settings');
				this.show('#index-status');
				this.updateProgress();
				break;

			case 'cancelled':
				this.show('#index-settings');
				this.hide('#index-status');
				break;

			case 'deleting':
				this.hide('#index-settings');
				this.show('#delete-status');
				this.setNotice('');
				break;

			case 'deleted':
				this.show('#index-settings');
				this.hide('#delete-status');
				break;

			case 'delete-error':
				this.show('#index-settings');
				this.hide('#delete-status');
				break;

			case 'delete-cancel':
				this.show('#index-settings');
				this.hide('#delete-status');
				break;

			default:
				break;
		}

		this.setMessage(this.state.message || '');
	}

	didLoadStart() {
		const message = __('Loading posts to index ...', 'block-catalog');

		this.setState({ status: 'loading', message });
		this.hideErrors();
		this.setNotice('');

		window.scrollTo(0, 0);
	}

	didLoadComplete(event) {
		const message = __('Loaded posts, starting ...', 'block-catalog');
		this.setState({ status: 'loaded', message, ...event.detail });

		const opts = {
			batchSize: this.settings?.index_batch_size,
		};

		this.indexer.index(this.state.posts, opts);
	}

	didLoadError(event) {
		const err = event.detail || {};

		let message = __('Failed to load posts to index.', 'block-catalog');

		if (err?.message) {
			message += `  (${err?.code} - ${err.message})`;
		}

		if (err?.data?.message) {
			message += `  (${err.data.message})`;
		} else if (typeof err?.data === 'string') {
			message += `  (${err.data})`;
		}

		this.setState({ status: 'load-error', message: '', error: err });
		this.setNotice(message, 'error');
	}

	didIndexStart(event) {
		const message = sprintf(
			__('Indexing %d / %d Posts ...', 'block-catalog'),
			event.detail.progress,
			event.detail.total,
		);

		this.setState({ status: 'indexing', message, ...event.detail });
	}

	didIndexProgress(event) {
		const message = sprintf(
			'Indexing %d / %d Posts ...',
			event.detail.progress,
			event.detail.total,
		);
		this.setState({ status: 'indexing', message, ...event.detail });
	}

	didIndexComplete(event) {
		let message;
		let type;

		if (event.detail.failures === 0) {
			message = sprintf(
				__('Indexed %d / %d Posts Successfully.', 'block-catalog'),
				event.detail.completed,
				event.detail.total,
			);
			type = 'success';
		} else if (event.detail.failures > 0 && event.detail.completed > 0) {
			message = sprintf(
				__('Indexed %d Posts successfully with %d Errors.', 'block-catalog'),
				event.detail.completed,
				event.detail.failures,
			);
			type = 'error';
		} else {
			message = sprintf(__('Failed to index %d Posts.', 'block-catalog'), event.detail.total);
			type = 'error';
		}

		this.setState({ status: 'settings', message: '', ...event.detail });
		this.setNotice(message, type);
	}

	didIndexCancel(event) {
		const message = __('Index cancelled.', 'block-catalog');
		this.setState({ status: 'cancelled', message: '', ...event.detail });
		this.setNotice(message, 'error');
	}

	didIndexError(event) {
		const err = event.detail || {};

		let message = __('Failed to index posts', 'block-catalog');

		if (err?.message) {
			message += `  (${err?.code} - ${err.message})`;
		}

		if (err?.data?.message) {
			message += `  (${err.data.message})`;
		} else if (typeof err?.data === 'string') {
			message += `  (${err.data})`;
		}

		this.addErrorLine(message);
	}

	didDeleteIndexStart(event) {
		const message = __('Deleting Index ...', 'block-catalog');
		this.setState({ status: 'deleting', message, ...event.detail });

		window.scrollTo(0, 0);
	}

	didDeleteIndexComplete(event) {
		let message;

		if (event.detail?.errors) {
			message = sprintf(
				__('Failed to delete %d catalog term(s).', 'block-catalog'),
				event.detail?.errors,
			);

			this.setNotice(message, 'error');
		} else if (event.detail?.removed) {
			message = sprintf(
				__('Deleted %d block catalog term(s) successfully.', 'block-catalog'),
				event.detail?.removed,
			);

			this.setNotice(message, 'success');
		} else {
			message = __('Nothing to delete, block catalog index is empty.', 'block-catalog');
			this.setNotice(message, 'error');
		}

		this.setState({ status: 'deleted', message: '', ...event.detail });
	}

	didDeleteIndexError(event) {
		const err = event.detail || {};

		let message = __('Deleting Index Failed.', 'block-catalog');

		if (err.errors) {
			message = sprintf(
				__('Failed to delete %d block catalog term(s).', 'block-catalog'),
				err.errors,
			);
		}

		if (err?.message) {
			message += `  (${err?.code} - ${err.message})`;
		}

		if (err?.data?.message) {
			message += `  (${err.data.message})`;
		} else if (typeof err?.data === 'string') {
			message += `  (${err.data})`;
		}

		this.setState({ status: 'delete-error', message: '', error: err });
		this.setNotice(message, 'error');
	}

	didDeleteIndexCancel(event) {
		const message = __('Deleting Index Cancelled.', 'block-catalog');
		this.setState({ status: 'delete-cancel', message: '', ...event.detail });
		this.setNotice(message, 'error');
	}

	didSubmitClick() {
		const opts = {
			postTypes: this.getSelectedPostTypes(),
		};

		this.indexer.load(opts);
		return false;
	}

	didCancelClick() {
		this.indexer.cancel();
		return false;
	}

	didDeleteIndexClick() {
		const message = __(
			'This will delete all terms in the Block Catalog index. Are you sure?',
			'block-catalog',
		);
		const res = confirm(message); // eslint-disable-line

		if (!res) {
			return false;
		}

		this.indexer.deleteIndex();
		return false;
	}

	didDeleteCancelClick() {
		this.indexer.cancelDelete();
		return false;
	}

	didPostTypesChange() {
		this.updateSubmitButton();
	}

	updateSubmitButton() {
		const postTypes = this.getSelectedPostTypes();
		const submitButton = document.querySelector('#submit');

		if (submitButton) {
			submitButton.disabled = postTypes.length === 0;
		}
	}

	getSelectedPostTypes() {
		const postTypes = document.querySelectorAll('.block-catalog-post-type:checked');

		if (!postTypes) {
			return [];
		}

		return [...postTypes].map((postType) => postType.value);
	}

	on(selector, event, handler) {
		const elements = document.querySelectorAll(selector);

		if (elements && elements.length) {
			elements.forEach((element) => {
				element.addEventListener(event, this[handler].bind(this));
			});
		}
	}

	onIndex(event, handler) {
		this.indexer.addEventListener(event, this[handler].bind(this));
	}

	show(selector) {
		const element = document.querySelector(selector);

		if (element) {
			element.style.display = 'block';
		}
	}

	hide(selector) {
		const element = document.querySelector(selector);

		if (element) {
			element.style.display = 'none';
		}
	}

	setMessage(message) {
		const element = document.querySelector('#index-message');

		if (element) {
			element.style.display = message !== '' ? 'block' : 'none';
			element.innerHTML = message;
		}
	}

	setNotice(message, type = 'success') {
		const container = document.querySelector('#index-notice');
		const element = document.querySelector('#index-notice-body');

		if (container) {
			container.style.display = message !== '' ? 'block' : 'none';
			container.className = `notice notice-${type}`;
		}

		if (element) {
			element.innerHTML = message;
		}
	}

	updateProgress() {
		const percent = this.indexer.total ? (this.indexer.progress / this.indexer.total) * 100 : 0;

		const element = document.querySelector('#index-progress');

		if (element) {
			element.value = percent;
		}
	}

	hideErrors() {
		const list = document.querySelector('#index-errors-list');

		if (list) {
			list.innerHTML = '';
		}

		const container = document.querySelector('#index-errors');

		if (container) {
			container.style.display = 'none';
		}
	}

	addErrorLine(line) {
		const container = document.querySelector('#index-errors');
		const list = document.querySelector('#index-errors-list');

		if (container) {
			container.style.display = 'block';
		}

		if (!list) {
			return;
		}

		const item = document.createElement('li');

		item.innerHTML = line;
		list.appendChild(item);
	}
}

document.addEventListener('DOMContentLoaded', () => {
	const settings = window.block_catalog?.settings || {};
	const app = new ToolsApp(settings);

	app.enable();
});
