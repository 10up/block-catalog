class Indexer extends EventTarget {
	load(opts) {
		this.progress = 0;
		this.total = 0;
		this.triggerEvent('loadStart');

		const fetchOpts = {
			url: opts.endpoint,
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
				} else if (res?.posts === undefined || res?.posts.length === 0) {
					this.triggerEvent('loadError', {
						code: 'invalid_response',
						message: 'Server returned empty posts.',
					});
				} else {
					this.triggerEvent('loadComplete', res);
				}

				return res;
			})
			.catch((err) => {
				this.triggerEvent('loadError', err);
			});
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
			try {
				await this.indexBatch(batch, opts); // eslint-disable-line no-await-in-loop
			} catch (err) {
				this.failures += batch.length;
				this.progress += batch.length;

				this.triggerEvent('indexProgress', {
					progress: this.progress,
					total: this.total,
				});

				this.triggerEvent('indexError', err);
			}
		}

		this.triggerEvent('indexComplete', {
			progress: this.progress,
			total: this.total,
			completed: this.completed,
			failures: this.failures,
		});
	}

	async indexBatch(batch, opts = {}) {
		const fetchOpts = {
			url: opts.endpoint,
			method: 'POST',
			data: {
				post_ids: batch,
			},
			...opts,
		};

		const promise = this.apiFetch(fetchOpts);

		promise.then((res) => {
			if (res.errors) {
				this.failures += batch.length;
				this.triggerEvent('indexError', res);
			} else if (!res.success && res.data) {
				this.failures += batch.length;
				this.triggerEvent('indexError', res);
			} else if (res?.updated === undefined) {
				this.failures += batch.length;
				this.triggerEvent('indexError', {
					code: 'invalid_response',
					message: 'Failed to index some posts',
				});
			} else {
				this.completed += batch.length;
			}

			this.progress += batch.length;
			this.triggerEvent('indexProgress', {
				progress: this.progress,
				total: this.total,
				...res,
			});
		});

		return promise;
	}

	cancel() {
		this.cancelPending();
		this.triggerEvent('indexCancel', {
			progress: this.progress,
			total: this.total,
		});
	}

	loadTerms(opts) {
		this.progress = 0;
		this.total = 0;
		this.triggerEvent('loadTermsStart');

		const fetchOpts = {
			url: opts.endpoint,
			method: 'POST',
			data: {},
			...opts,
		};

		return this.apiFetch(fetchOpts)
			.then((res) => {
				if (res.errors) {
					this.triggerEvent('loadTermsError', res);
				} else if (!res.success && res.data) {
					this.triggerEvent('loadTermsError', res);
				} else if (res?.terms === undefined || res?.terms.length === 0) {
					this.triggerEvent('loadTermsError', {
						code: 'invalid_response',
						message: 'Server returned empty terms.',
						...res,
					});
				} else {
					this.triggerEvent('loadTermsComplete', res);
				}

				return res;
			})
			.catch((err) => {
				this.triggerEvent('loadTermsError', err);
			});
	}

	async deleteIndex(opts) {
		this.progress = 0;
		this.completed = 0;
		this.failures = 0;
		this.total = 0;
		this.triggerEvent('deleteIndexStart');

		const fetchOpts = {
			url: opts.endpoint,
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

	cancelDelete() {
		this.cancelPending();
		this.triggerEvent('deleteIndexCancel');
	}

	triggerEvent(eventName, data = {}) {
		const event = new CustomEvent(eventName, { detail: data });
		this.dispatchEvent(event);
	}

	toChunks(list, chunkSize = 50) {
		const first = list.shift();
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
