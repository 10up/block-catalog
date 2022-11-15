import Indexer from './indexer';

class ToolsApp {
	enable() {
		this.indexer = new Indexer();
		this.state = { status: 'settings', message: '' };

		this.onIndex('loadStart', 'didLoadStart');
		this.onIndex('loadComplete', 'didLoadComplete');
		this.onIndex('indexStart', 'didIndexStart');
		this.onIndex('indexProgress', 'didIndexProgress');
		this.onIndex('indexComplete', 'didIndexComplete');
		this.onIndex('indexCancel', 'didIndexCancel');
		this.onIndex('indexError', 'didIndexError');

		this.on('.block-catalog-post-type', 'change', 'didPostTypesChange');
		this.on('#submit', 'click', 'didSubmitClick');
		this.on('#cancel', 'click', 'didCancelClick');
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

			default:
				break;
		}

		this.setMessage(this.state.message || '');
	}

	didLoadStart() {
		this.setState({ status: 'loading', message: 'Loading posts to index ...' });
		this.hideErrors();
	}

	didLoadComplete(event) {
		this.setState({ status: 'loaded', message: 'Loaded posts, starting ...', ...event.detail });
		this.indexer.index(this.state.posts);
	}

	didIndexStart(event) {
		const message = `Indexing ${event.detail.progress}/${event.detail.total} Posts ...`;
		this.setState({ status: 'indexing', message, ...event.detail });
	}

	didIndexProgress(event) {
		const message = `Indexing ${event.detail.progress}/${event.detail.total} Posts ...`;
		this.setState({ status: 'indexing', message, ...event.detail });
	}

	didIndexComplete(event) {
		const message = `Indexed ${event.detail.progress}/${event.detail.total} Posts.`;
		this.setState({ status: 'settings', message, ...event.detail });
	}

	didIndexCancel(event) {
		const message = 'Index cancelled.';
		this.setState({ status: 'cancelled', message, ...event.detail });
	}

	didIndexError(event) {
		this.addErrorLine(event.detail.error || 'Unexpected error occurred');
	}

	didSubmitClick() {
		this.indexer.load();
		return false;
	}

	didCancelClick() {
		this.indexer.cancel();
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
		const notice = document.querySelector('#index-message-notice');

		if (notice) {
			notice.style.display = message !== '' ? 'block' : 'none';
		}

		const element = document.querySelector('#index-message');

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

		item.appendChild(text);
		list.appendChild(item);
	}
}

document.addEventListener('DOMContentLoaded', () => {
	const app = new ToolsApp();
	app.enable();
});
