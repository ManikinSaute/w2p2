
(function (wp) {
	const { __ } = wp.i18n;
	const { registerPlugin } = wp.plugins;
	const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
	const { PanelBody, Notice, Spinner } = wp.components;
	const { createElement: el, useState } = wp.element;
	const { useDispatch, select, subscribe } = wp.data;
    

    async function logToPhp(message, level = "info") {
    try {
        await fetch(W2P2_LOGGER.ajaxUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                action: "w2p2_log_message",
                nonce: W2P2_LOGGER.nonce,
                message,
                level
            }),
            credentials: "same-origin"
        });
    } catch (err) {
        console.error("Sidebar.js - Failed to log to PHP:", err);
    }
}

	if (!wp || !wp.data || !select) return;
	const expectedPT = (window.MGC_SETTINGS && MGC_SETTINGS.postType) || 'w2p2_import';
    // only allow UI to show on this special CPT we do in JS and PHP
	const unsubscribe = subscribe(() => {
		const pt =
			(select('core/editor')?.getCurrentPostType?.()) ||
			(select('core/editor')?.getEditedPostAttribute?.('type'));
		const postId = select('core/editor')?.getCurrentPostId?.();

		if (!pt || !postId) return;           
		if (pt !== expectedPT) { unsubscribe(); return; }

		unsubscribe();

		function MammothSidebar() {
			const { insertBlocks } = useDispatch('core/block-editor');
			const [loading, setLoading] = useState(false);
			const [messages, setMessages] = useState([]);
			const [selected, setSelected] = useState(null);

                wp.element.useEffect(() => {
                    if (messages.length > 0) {
                        messages.forEach((m, i) => {
                            logToPhp(
                                `Message[${i}] - type: ${m.type || 'info'} - content: ${
                                    m.message || JSON.stringify(m)
                                }`,
                                m.type === 'warning'
                                    ? 'warning'
                                    : m.type === 'success'
                                        ? 'success'
                                        : 'info'
                            );
                        });
                    }
                }, [messages]);

			async function handleFile(e) {
				const file = e.target.files?.[0];
				if (!file) return;

                await logToPhp(`Sidebar.js - User selected file: ${file.name}`, "info");

				if (!file.name.toLowerCase().endsWith('.docx')) {
					window.alert(__('Sidebar.js - Please select a valid .docx file.', 'mgc'));
                    await logToPhp(`Sidebar.js - Please select a valid .docx file. ${file.name}`, "info");
					return;
				}

				setMessages([]);
				setSelected(file.name);
				setLoading(true);

				try {
					const arrayBuffer = await file.arrayBuffer();
					const result = await window.mammoth.convertToHtml({ arrayBuffer });
					const rawHtml = result.value || '';
					setMessages(result.messages || []);

					const res = await fetch(MGC_SETTINGS.ajaxUrl, {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: new URLSearchParams({
							action: 'mgc_sanitize_html',
							nonce: MGC_SETTINGS.nonce,
							html: rawHtml,
                            filename: selected || file.name,
						}),
						credentials: 'same-origin',
					});

					const data = await res.json();
					if (!data || !data.success) {
                        await logToPhp(`Sidebar.js - Sanitisation failed`, "error");
						throw new Error((data && data.data && data.data.message) || 'Sidebar.js - Sanitisation failed');
					}

					const safeHtml = data.data.html || '';
					const blocks = wp.blocks.rawHandler({ HTML: safeHtml });
					if (blocks && blocks.length) {
                        setMessages(prev => [
                                ...prev,
                                { type: 'success', message: __('Sidebar.js - File successfully inserted into the editor: ', 'mgc') + (selected || file.name) }
                            ]);
                            await logToPhp(`Sidebar.js - File successfully inserted into the editor 2: ${selected || file.name}`, 'success');
						insertBlocks(blocks);
					} else {
                        await logToPhp(`Sidebar.js - Nothing to insert`, "error");
						window.alert(__('Sidebar.js - Nothing to insert — the file may be empty after sanitisation.', 'mgc'));
					}
				} catch (err) {
                    await logToPhp(`Sidebar.js - Word conversion failed`, "error");
					console.error('Sidebar.js - Word conversion failed:', err);
					window.alert(__('Sidebar.js - Failed to convert or insert file. See console for details.', 'mgc'));
				} finally {
					setLoading(false);
					e.target.value = '';
				}
			}

			return el(
				wp.element.Fragment,
				null,
				el(PluginSidebarMoreMenuItem, { target: 'mgc-sidebar' }, 'Mammoth DOCX'),
				el(
					PluginSidebar,
					{ name: 'mgc-sidebar', title: __('Convert Word to WordPress', 'mgc') },
					el(
						PanelBody,
						{ title: __('Select a Word .docx file', 'mgc'), initialOpen: true },
						el('p', null, __('Select a .docx file to convert to a Gutenberg WordPress post. Content is sanitised with wp_kses_post() before insertion, no files are stored on the server.', 'mgc')),
                        el('p', null, __('Posts here can not be sent for review, no one will review this post when it has been saved.', 'mgc')),
                        el('p', null, __('You will need to copy and paste the content out or speak to a member of the wb ops team.', 'mgc')),
						el('input', {
							type: 'file',
							accept: '.docx',
							onChange: handleFile,
							'aria-label': __('Select .docx file', 'mgc'),
							disabled: loading,
						}),
						selected && el('p', { style: { marginTop: 8 } }, __('Selected:', 'mgc'), ' ', el('strong', null, selected)),
						loading && el('p', { style: { marginTop: 8 } }, el(Spinner, null), ' ', __('Converting…', 'mgc')),
                        !loading && selected && el(
                            wp.components.Notice,
                            { status: 'success', isDismissible: false, style: { marginTop: 8 } },
                            __('The file was successfully added to the page.', 'mgc')
                        )                        
					)
				)
			);
		}
		registerPlugin('mgc-sidebar', { render: MammothSidebar });
	}); 
})(window.wp);
