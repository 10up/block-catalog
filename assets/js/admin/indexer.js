class Indexer extends EventTarget {
	load(opts) {
		this.cancelled = false;
		this.progress = 0;
		this.total = 0;
		this.triggerEvent('loadStart');

		const fetchOpts = {
			path: '/block-catalog/v1/posts',
			method: 'POST',
			data: {
				post_types: opts.postTypes || [],
			},
			...opts,
		};

		return wp.apiFetch(fetchOpts).then((res) => {
			this.triggerEvent('loadComplete', res);
			return res;
		});
	}

	deleteIndex() {
		this.cancelled = false;
		this.triggerEvent('deleteIndexStart');

		const fetchOpts = {
			path: '/block-catalog/v1/delete-index',
			method: 'POST',
		};

		this.deletePromise = wp.apiFetch(fetchOpts)
		.then((res) => {
			if (this.deletePromise?.cancelled) {
				return res;
			}

			if (res.errors) {
				this.triggerEvent('deleteIndexError', res);
			} else if (!res.success && res.data) {
				this.triggerEvent('deleteIndexError', res);
			} else {
				this.triggerEvent('deleteIndexComplete', res);
			}

			this.deletePromise = null;
			return res;
		})
		.catch((err) => {
			this.triggerEvent('deleteIndexError', err);
		});

		return this.deletePromise;
	}

	cancelDelete(opts) {
		if (this.deletePromise) {
			this.deletePromise.cancelled = true;
		}

		this.cancelled = false;
		this.triggerEvent('deleteIndexCancel');
	}

	async index(ids, opts) {
		this.progress = 0;
		this.total = ids.length;
		this.triggerEvent('indexStart', { progress: 0, total: this.total });

		const chunks = this.toChunks(ids);
		const n = chunks.length;

		for (let i = 0; i < n; i++) {
			if (this.cancelled) {
				return;
			}

			const batch = chunks[i];
			await this.indexBatch(batch, opts); // eslint-disable-line no-await-in-loop
		}

		this.triggerEvent('indexComplete', { progress: this.progress, total: this.total });
	}

	async indexBatch(batch, opts = {}) {
		const fetchOpts = {
			path: '/block-catalog/v1/index',
			method: 'POST',
			data: {
				post_ids: batch,
			},
			...opts,
		};

		return wp.apiFetch(fetchOpts).then((changes) => {
			this.progress += batch.length;
			this.triggerEvent('indexProgress', {
				progress: this.progress,
				total: this.total,
				...changes,
			});
		});
	}

	cancel() {
		this.triggerEvent('indexCancel', { progress: this.progress, total: this.total });
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
		const first  = list.shift();
		const output = [];

		for (let i = 0; i < list.length; i += chunkSize) {
			output.push(list.slice(i, i + chunkSize));
		}

		output.unshift([first]);

		return output;
	}
}

export default Indexer;
