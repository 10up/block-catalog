class Indexer extends EventTarget {

	load() {
		this.cancelled = false;
		this.progress  = 0;
		this.total     = 0;
		this.triggerEvent('loadStart');

		return wp.apiFetch({path: '/block-catalog/v1/posts'})
			.then((res) => {
				this.triggerEvent('loadComplete', res);
				return res;
			});
	}

	async index(ids, opts) {
		this.progress = 0;
		this.total    = ids.length;
		this.triggerEvent('indexStart', {progress: 0, total: this.total});

		const chunks    = this.toChunks(ids);
		const n         = chunks.length;

		for (let i = 0; i < n; i++) {
			if (this.cancelled) {
				return;
			}

			const batch = chunks[i];
			await this.indexBatch(batch, opts);
		}

		this.triggerEvent('indexComplete', {progress:this.progress, total:this.total});
	}

	async indexBatch(batch, opts) {
		const fetchOpts = {
			path: '/block-catalog/v1/index',
			method: 'POST',
			data: {
				post_ids: batch,
			}
		};

		return wp.apiFetch(fetchOpts)
			.then((changes) => {
				this.progress += batch.length;
				this.triggerEvent('indexProgress', {progress:this.progress, total:this.total});
			});
	}

	cancel() {
		this.triggerEvent('indexCancel', {progress:this.progress, total:this.total});
		this.cancelled = true;
	}

	triggerEvent(eventName, data = {}) {
		if (this.cancelled) {
			return;
		}

		const event = new CustomEvent(eventName, { detail: data });
		this.dispatchEvent(event);
	}

	toChunks(list, chunkSize = 100) {
		const output = [];

		for (let i = 0; i < list.length; i += chunkSize) {
			output.push(list.slice(i, i + chunkSize));
		}

		return output;
	}

}

class ToolsApp {

	enable() {
		this.indexer = new Indexer();
		this.state   = {status: 'settings', message:''};

		this.onIndex('loadStart', 'didLoadStart' );
		this.onIndex('loadComplete', 'didLoadComplete' );
		this.onIndex('indexStart', 'didIndexStart' );
		this.onIndex('indexProgress', 'didIndexProgress' );
		this.onIndex('indexComplete', 'didIndexComplete' );
		this.onIndex('indexCancel', 'didIndexCancel' );
		this.onIndex('indexError', 'didIndexError' );

		this.on('.block-catalog-post-type', 'change', 'didPostTypesChange');
		this.on('#submit', 'click', 'didSubmitClick' );
		this.on('#cancel', 'click', 'didCancelClick' );
	}

	setState(state) {
		this.prevState = this.state;
		this.state     = state;

		const settingsSection = document.querySelector('#index-settings');
		const statusSection   = document.querySelector('#index-status');

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
		}

		this.setMessage(this.state.message || '');
	}

	didLoadStart(event) {
		this.setState({status:'loading', message: 'Loading posts to index ...'});
		this.hideErrors();
	}

	didLoadComplete(event) {
		this.setState({status:'loaded', message: 'Loaded posts, starting ...', ...event.detail});
		this.indexer.index(this.state.posts);
	}

	didIndexStart(event) {
		const message = `Indexing ${event.detail.progress}/${event.detail.total} Posts ...`;
		this.setState({status: 'indexing', message, ...event.detail});
	}

	didIndexProgress(event) {
		const message = `Indexing ${event.detail.progress}/${event.detail.total} Posts ...`;
		this.setState({status: 'indexing', message, ...event.detail});
	}

	didIndexComplete(event) {
		const message = `Indexed ${event.detail.progress}/${event.detail.total} Posts.`;
		this.setState({status: 'settings', message, ...event.detail});
	}

	didIndexCancel(event) {
		const message = 'Index cancelled.';
		this.setState({status: 'cancelled', message, ...event.detail});
	}

	didIndexError(event) {
		this.addErrorLine(event.detail.error || 'Unexpected error occurred');
	}

	didSubmitClick(event) {
		this.indexer.load();
		return false;
	}

	didCancelClick(event) {
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

		const values = [];

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
		const percent = this.indexer.total ? this.indexer.progress / this.indexer.total * 100 : 0;

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
		const list      = document.querySelector('#index-errors-list');

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
