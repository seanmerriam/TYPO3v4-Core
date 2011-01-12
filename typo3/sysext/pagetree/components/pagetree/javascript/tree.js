/***************************************************************
*  Copyright notice
*
*  (c) 2010 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
Ext.namespace('TYPO3.Components.PageTree');

/**
 * @class TYPO3.Components.PageTree.Tree
 *
 * Generic Tree Panel
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.tree.TreePanel
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
TYPO3.Components.PageTree.Tree = Ext.extend(Ext.tree.TreePanel, {
	/**
	 * Border
	 *
	 * @type {Boolean}
	 */
	border: false,

	/**
	 * Indicates if the root node is visible
	 *
	 * @type {Boolean}
	 */
	rootVisible: false,

	/**
	 * Enable the drag and drop feature
	 *
	 * @cfg {Boolean}
	 */
	enableDD: true,

	/**
	 * Drag and Drop Group
	 *
	 * @cfg {String}
	 */
	ddGroup: '',

	/**
	 * Indicates if the label should be editable
	 *
	 * @cfg {Boolean}
	 */
	labelEdit: true,

	/**
	 * User Interface Provider
	 *
	 * @cfg {Ext.tree.TreeNodeUI}
	 */
	uiProvider: null,

	/**
	 * Data Provider
	 *
	 * @cfg {Object}
	 */
	treeDataProvider: null,

	/**
	 * Command Provider
	 *
	 * @cfg {Object}
	 */
	commandProvider : null,

	/**
	 * Context menu provider
	 *
	 * @cfg {Object}
	 */
	contextMenuProvider: null,

	/**
	 * Root Node Configuration
	 *
	 * @type {Object}
	 */
	rootNodeConfig: {
		id: 'root',
		expanded: true
	},

	/**
	 * Indicator if the control key is pressed
	 *
	 * @type {Boolean}
	 */
	isControlPressed: false,

	/**
	 * Context Node
	 *
	 * @type {Ext.tree.TreeNode}
	 */
	t3ContextNode: null,

	/**
	 * Context Information
	 *
	 * @type {Object}
	 */
	t3ContextInfo: {
		inCopyMode: false,
		inCutMode: false
	},

	/**
	 * Listeners
	 *
	 * Event handlers that handle click events and synchronizes the label edit,
	 * double click and single click events in a useful way.
	 */
	listeners: {
			// single click handler that only triggers after a delay to let the double click event
			// a possibility to be executed (needed for label edit)
		click: {
			fn: function(node, event) {
				if (this.clicksRegistered === 2) {
					this.clicksRegistered = 0;
					event.stopEvent();
					return false;
				}

				this.clicksRegistered = 0;
				if (this.commandProvider.singleClick) {
					this.commandProvider.singleClick(node, this);
				}
			},
			delay: 400
		},

			// prevent the expanding / collapsing on double click
		beforedblclick: {
			fn: function() {
				return false;
			}
		},

			// prevents label edit on a selected node
		beforeclick: {
			fn: function(node, event) {
				if (!this.clicksRegistered && this.getSelectionModel().isSelected(node)) {
					node.fireEvent('click', node, event);
					++this.clicksRegistered;
					return false;
				}
				++this.clicksRegistered;
			}
		}
	},

	/**
	 * Initializes the component
	 *
	 * @return {void}
	 */
	initComponent: function() {
		if (!this.uiProvider) {
			this.uiProvider = TYPO3.Components.PageTree.PageTreeNodeUI;
		}

		this.root = new Ext.tree.AsyncTreeNode(this.rootNodeConfig);
		this.addTreeLoader();

		if (this.labelEdit) {
			this.enableInlineEditor();
		}

		if (this.enableDD) {
			this.dragConfig = {ddGroup: this.ddGroup};
			this.enableDragAndDrop();
		}

		if (this.contextMenuProvider) {
			this.enableContextMenu();
		}

		TYPO3.Components.PageTree.Tree.superclass.initComponent.apply(this, arguments);
	},

	/**
	 * Refreshes the tree
	 *
	 * @param {Function} callback
	 * @param {Object} scope
	 * return {void}
	 */
	refreshTree: function(callback, scope) {
		this.refreshNode(this.root, callback, scope);
	},

	/**
	 * Refreshes a given node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {Function} callback
	 * @param {Object} scope
	 * return {void}
	 */
	refreshNode: function(node, callback, scope) {
		if (this.inRefreshingMode) {
			return;
		}

		scope = scope || node;
		this.inRefreshingMode = true;
		var loadCallback = function(node) {
			node.ownerTree.inRefreshingMode = false;
			if (node.ownerTree.restoreState) {
				node.ownerTree.restoreState(node.getPath());
			}
		};

		if (callback) {
			loadCallback = callback.createSequence(loadCallback);
		}

		this.getLoader().load(node, loadCallback, scope);
	},

	/**
	 * Adds a tree loader implementation that uses the directFn feature
	 *
	 * return {void}
	 */
	addTreeLoader: function() {
		this.loader = new Ext.tree.TreeLoader({
			directFn: this.treeDataProvider.getNextTreeLevel,
			paramOrder: 'attributes',
			baseAttrs: {
				uiProvider: this.uiProvider
			},

				// an id can never be zero in ExtJS, but this is needed
				// for the root line feature or it will never be working!
			createNode: function(attr) {
				if (attr.id == 0) {
					attr.id = 'siteRootNode';
				}

				return Ext.tree.TreeLoader.prototype.createNode.call(this, attr);
			},

			listeners: {
				beforeload: function(treeLoader, node) {
					node.ownerTree.previousOpenedStateHash = {};
					treeLoader.baseParams.attributes = node.attributes.nodeData;
				}
			}
		});
	},

	/**
	 * Enables the context menu feature
	 *
	 * return {void}
	 */
	enableContextMenu: function() {
		this.contextMenu = new TYPO3.Components.PageTree.ContextMenu();

		this.on('contextmenu', function(node, event) {
			this.openContextMenu(node, event);
		});
	},

	/**
	 * Open a context menu for the given node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {Ext.EventObject} event
	 * return {void}
	 */
	openContextMenu: function(node, event) {
		var attributes = Ext.apply(node.attributes.nodeData, {
			t3ContextInfo: node.ownerTree.t3ContextInfo
		});

		this.contextMenuProvider.getActionsForNodeArray(
			attributes,
			function(configuration) {
				this.contextMenu.removeAll();
				this.contextMenu.fill(node, this, configuration);
				if (this.contextMenu.items.length) {
					this.contextMenu.showAt(event.getXY());

				}
			},
			this
		);
	},

	/**
	 * Initialize the inline editor for the given tree.
	 *
	 * @return {void}
	 */
	enableInlineEditor: function() {
		(new TYPO3.Components.PageTree.TreeEditor(this));
	},

	/**
	 * Enables the drag and drop feature
	 *
	 * return {void}
	 */
	enableDragAndDrop: function() {
			// init proxy element
		this.on('startdrag', this.initDDProxyElement, this);

			// node is moved
		this.on('movenode', this.moveNode, this);

			// new node is created/copied
		this.on('beforenodedrop', this.beforeDropNode, this);
		this.on('nodedrop', this.dropNode, this);

			// listens on the ctrl key to toggle the copy mode
		(new Ext.KeyMap(document, {
			key: Ext.EventObject.CONTROL,
			scope: this,
			buffer: 250,
			fn: function() {
				if (this.dragZone.dragging && this.copyHint) {
					this.shouldCopyNode = !this.shouldCopyNode;
					this.copyHint.toggle();
				}
			}
		}, 'keydown'));

			// listens on the escape key to stop the dragging
		(new Ext.KeyMap(document, {
			key: Ext.EventObject.ESC,
			scope: this,
			buffer: 250,
			fn: function(event) {
				if (this.dragZone.dragging) {
					Ext.dd.DragDropMgr.stopDrag(event);
					this.dragZone.onInvalidDrop(event);
				}
			}
		}, 'keydown'));
	},

	/**
	 * Adds the copy hint to the proxy element
	 *
	 * @return {void}
	 */
	initDDProxyElement: function() {
		this.shouldCopyNode = false;
		this.copyHint = new Ext.Element(document.createElement('div'))
			.addClass(this.id + '-copyHelp');
		this.copyHint.dom.appendChild(document.createTextNode(TYPO3.Components.PageTree.LLL.copyHint));
		this.copyHint.setVisibilityMode(Ext.Element.DISPLAY);

		this.dragZone.proxy.ghost.dom.appendChild(this.copyHint.dom);
	},

	/**
	 * Creates a Fake Node
	 *
	 * This must be done to prevent the calling of the moveNode event.
	 *
	 * @param {object} dragElement
	 */
	beforeDropNode: function(dragElement) {
		if (dragElement.data && dragElement.data.item && dragElement.data.item.shouldCreateNewNode) {
			this.t3ContextInfo.serverNodeType = dragElement.data.item.nodeType;
			dragElement.dropNode = new Ext.tree.TreeNode({
				text: TYPO3.Components.PageTree.LLL.fakeNodeHint,
				leaf: true,
				isInsertedNode: true
			});

				// fix incorrect cancel value
			dragElement.cancel = false;

		} else if (this.shouldCopyNode) {
			var attributes = dragElement.dropNode.attributes;
			attributes.isCopiedNode = true;
			attributes.id = 'fakeNode';
			dragElement.dropNode = new Ext.tree.TreeNode(attributes);
		}

		return true;
	},

	/**
	 * Differentiate between the copy and insert event
	 *
	 * @param {Ext.tree.TreeDropZone} dragElement
	 * return {void}
	 */
	dropNode: function(dragElement) {
		if (dragElement.dropNode.attributes.isInsertedNode) {
			dragElement.dropNode.attributes.isInsertedNode = false;
			this.insertNode(dragElement.dropNode);
		} else if (dragElement.dropNode.attributes.isCopiedNode) {
			dragElement.dropNode.attributes.isCopiedNode = false;
			this.copyNode(dragElement.dropNode)
		}
	},

	/**
	 * Moves a node
	 *
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @param {Ext.tree.TreeNode} movedNode
	 * @param {Ext.tree.TreeNode} oldParent
	 * @param {Ext.tree.TreeNode} newParent
	 * @param {int} position
	 * return {void}
	 */
	moveNode: function(tree, movedNode, oldParent, newParent, position) {
		tree.t3ContextNode = movedNode;

		if (position === 0) {
			this.commandProvider.moveNodeToFirstChildOfDestination(newParent, tree);
		} else {
			var previousSiblingNode = newParent.childNodes[position - 1];
			this.commandProvider.moveNodeAfterDestination(previousSiblingNode, tree);
		}
	},

	/**
	 * Inserts a node
	 *
	 * @param {Ext.tree.TreeNode} movedNode
	 * return {void}
	 */
	insertNode: function(movedNode) {
		this.t3ContextNode = movedNode.parentNode;

		movedNode.disable();
		if (movedNode.previousSibling) {
			this.commandProvider.insertNodeAfterDestination(movedNode, this);
		} else {
			this.commandProvider.insertNodeToFirstChildOfDestination(movedNode, this);
		}
	},

	/**
	 * Copies a node
	 *
	 * @param {Ext.tree.TreeNode} movedNode
	 * return {void}
	 */
	copyNode: function(movedNode) {
		this.t3ContextNode = movedNode;

		movedNode.disable();
		if (movedNode.previousSibling) {
			this.commandProvider.copyNodeAfterDestination(movedNode, this);
		} else {
			this.commandProvider.copyNodeToFirstChildOfDestination(movedNode, this);
		}
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.Tree', TYPO3.Components.PageTree.Tree);