class Indexer extends EventTarget {
	load(opts) {
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

		return this.apiFetch(fetchOpts)
		.then((res) => {
			if (res.errors) {
				this.triggerEvent('loadError', res);
			} else if (!res.success && res.data) {
				this.triggerEvent('loadError', res);
			} else if (res?.posts.length === 0) {
				this.triggerEvent('loadError', {code: 'invalid_response', message: 'Server returned empty posts.'});
			} else {
				this.triggerEvent('loadComplete', res);
			}

			return res;
		})
		.catch((err) => {
			this.triggerEvent('loadError', err);
		});
	}

	deleteIndex() {
		this.triggerEvent('deleteIndexStart');

		const fetchOpts = {
			path: '/block-catalog/v1/delete-index',
			method: 'POST',
		};

		const promise = this.apiFetch(fetchOpts)
			.then((res) => {
				if (res.errors) {
					this.triggerEvent('deleteIndexError', res);
				} else if (!res.success && res.data) {
					this.triggerEvent('deleteIndexError', res);
				} else {
					this.triggerEvent('deleteIndexComplete', res);
				}

				return res;
			})
			.catch((err) => {
				this.triggerEvent('deleteIndexError', err);
			});

		return promise;
	}

	cancelDelete(opts) {
		this.cancelPending();
		this.triggerEvent('deleteIndexCancel');
	}

	async index(ids, opts) {
		this.progress = 0;
		this.completed = 0;
		this.failures = 0;
		this.total = ids.length;
		this.triggerEvent('indexStart', { progress: 0, total: this.total });

		const chunks = this.toChunks(ids, opts.batchSize || 50);
		const n = chunks.length;

		for (let i = 0; i < n; i++) {
			const batch = chunks[i];
			await this.indexBatch(batch, opts); // eslint-disable-line no-await-in-loop
		}

		this.triggerEvent('indexComplete', { progress: this.progress, total: this.total, completed: this.completed, failures: this.failures });
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

		const promise = this.apiFetch(fetchOpts)

		promise.then((res) => {
			if (res.errors) {
				this.failures += batch.length;
				this.triggerEvent('indexError', res);
			} else if (!res.success && res.data) {
				this.failures += batch.length;
				this.triggerEvent('indexError', res);
			} else {
				this.completed += batch.length;
			}

			this.progress += batch.length;
			this.triggerEvent('indexProgress', {
				progress: this.progress,
				total: this.total,
				...res,
			});
		})
		.catch((err) => {
			this.failures += batch.length;
			this.triggerEvent('indexError', err);
		});

		return promise;
	}

	cancel() {
		this.cancelPending();
		this.triggerEvent('indexCancel', { progress: this.progress, total: this.total });
	}

	triggerEvent(eventName, data = {}) {
		const event = new CustomEvent(eventName, { detail: data });
		this.dispatchEvent(event);
	}

	toChunks(list, chunkSize = 50) {
		const first  = list.shift();
		const output = [];

		for (let i = 0; i < list.length; i += chunkSize) {
			output.push(list.slice(i, i + chunkSize));
		}

		output.unshift([first]);

		return output;
	}

	cancelPending() {
		if (this.pending?.length) {
			for (let i = 0; i < this.pending.length; i++) {
				const promise = this.pending[i];
				promise.cancelled = true;
			}
		}

		this.pending = [];
	}

	apiFetch(opts) {
		if (!this.pending) {
			this.pending = [];
		}

		this.cancelPending();

		const promise = this.apiFetchWithCancel(opts);
		this.pending.push(promise);

		return promise;
	}

	apiFetchWithCancel(opts) {
		const request = wp.apiFetch(opts);
		const wrapper = new Promise((resolve, reject) => {
			request
				.then((res) => {
					if (wrapper?.cancelled) {
						return;
					}

					resolve(res);
				})
				.catch((err) => {
					if (wrapper?.cancelled) {
						return;
					}

					reject(err);
				});
		});

		wrapper.request = request;

		return wrapper;
	}

}

export default Indexer;
