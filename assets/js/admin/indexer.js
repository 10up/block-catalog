class Indexer extends EventTarget {
	load() {
		this.cancelled = false;
		this.progress = 0;
		this.total = 0;
		this.triggerEvent('loadStart');

		return wp.apiFetch({ path: '/block-catalog/v1/posts' }).then((res) => {
			this.triggerEvent('loadComplete', res);
			return res;
		});
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
		const output = [];

		for (let i = 0; i < list.length; i += chunkSize) {
			output.push(list.slice(i, i + chunkSize));
		}

		return output;
	}
}

export default Indexer;
