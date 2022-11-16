import Indexer from './indexer';

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
		this.setState({ status: 'loading', message: 'Loading posts to index ...' });
		this.hideErrors();
		this.setNotice('');

		window.scrollTo(0, 0);
	}

	didLoadComplete(event) {
		this.setState({ status: 'loaded', message: 'Loaded posts, starting ...', ...event.detail });

		const opts = {
			batchSize: this.settings?.index_batch_size,
		};

		this.indexer.index(this.state.posts, opts);
	}

	didLoadError(event) {
		const err = event.detail || {};

		let message = 'Failed to load posts to index.';

		if (err?.message) {
			message += `  (${err?.code} - ${err.message})`;
		}

		if (err?.data?.message) {
			message += `  (${err.data.message})`;
		} else if ( typeof(err?.data) === 'string') {
			message += `  (${err.data})`;
		}

		this.setState({ status: 'load-error', message: '', error: err});
		this.setNotice(message, 'error');
	}

	didIndexStart(event) {
		const message = `Indexing ${event.detail.progress} / ${event.detail.total} Posts ...`;
		this.setState({ status: 'indexing', message, ...event.detail });
	}

	didIndexProgress(event) {
		const message = `Indexing ${event.detail.progress} / ${event.detail.total} Posts ...`;
		this.setState({ status: 'indexing', message, ...event.detail });
	}

	didIndexComplete(event) {
		let message;
		let type;

		if (event.detail.failures === 0) {
			message = `Indexed ${event.detail.completed} / ${event.detail.total} Posts Successfully.`;
			type    = 'success';
		} else if (event.detail.failures > 0 && event.detail.completed > 0) {
			message = `Indexed ${event.detail.completed} Posts successfully with ${event.detail.failures} Errors.`;
			type    = 'error';
		} else {
			message = `Failed to index ${event.detail.total} Posts.`;
			type    = 'error';
		}

		this.setState({ status: 'settings', message: '', ...event.detail });
		this.setNotice(message, type);
	}

	didIndexCancel(event) {
		const message = 'Index cancelled.';
		this.setState({ status: 'cancelled', message: '', ...event.detail });
		this.setNotice(message, 'error');
	}

	didIndexError(event) {
		const err = event.detail || {};

		let message = 'Failed to index posts';

		if (err?.message) {
			message += `  (${err?.code} - ${err.message})`;
		}

		if (err?.data?.message) {
			message += `  (${err.data.message})`;
		} else if ( typeof(err?.data) === 'string') {
			message += `  (${err.data})`;
		}

		this.addErrorLine(message);
	}

	didDeleteIndexStart(event) {
		const message = 'Deleting Index ...';
		this.setState({ status: 'deleting', message, ...event.detail });

		window.scrollTo(0, 0);
	}

	didDeleteIndexComplete(event) {
		let message;

		if (event.detail?.errors) {
			message = `Failed to delete ${event.detail?.errors} catalog term(s).`;
			this.setNotice(message, 'error');
		} else if (event.detail?.removed) {
			message = `Deleted ${event.detail?.removed} block catalog term(s) successfully.`;
			this.setNotice(message, 'success');
		} else {
			message = 'Nothing to delete, block catalog index is empty.';
			this.setNotice(message, 'error');
		}

		this.setState({ status: 'deleted', message:'', ...event.detail });
	}

	didDeleteIndexError(event) {
		const err = event.detail || {};

		let message = 'Deleting Index Failed.';

		if (err.errors) {
			message = `Failed to delete ${err.errors} block catalog term(s).`;
		}

		if (err?.message) {
			message += `  (${err?.code} - ${err.message})`;
		}

		if (err?.data?.message) {
			message += `  (${err.data.message})`;
		} else if ( typeof(err?.data) === 'string') {
			message += `  (${err.data})`;
		}

		this.setState({ status: 'delete-error', message:'', error: err });
		this.setNotice(message, 'error');
	}

	didDeleteIndexCancel(event) {
		const message = 'Deleting Index Cancelled.';
		this.setState({ status: 'delete-cancel', message:'', ...event.detail });
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
		const res = confirm('This will delete all terms in the Block Catalog index. Are you sure?');

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
		const text = document.createTextNode(line);

		item.innerHTML = line;
		list.appendChild(item);
	}

}

document.addEventListener('DOMContentLoaded', () => {
	const settings = window.block_catalog?.settings || {};
	const app      = new ToolsApp(settings);

	app.enable();
});
