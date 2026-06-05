// Product Customization Store
export default {
    namespaced: true,

    state: () => ({
        // 当前的打印区域
        printAreas: [],

        // 当前设计元素
        elements: [],

        // 选中的元素
        selectedElement: null,

        // 是否在编辑模式
        isEditing: false,

        // 当前图片URL
        currentImage: null,
    }),

    mutations: {
        setPrintAreas(state, areas) {
            state.printAreas = areas;
        },

        addElement(state, element) {
            state.elements.push(element);
        },

        updateElement(state, { id, updates }) {
            const index = state.elements.findIndex(el => el.id === id);
            if (index !== -1) {
                state.elements[index] = { ...state.elements[index], ...updates };
            }
        },

        removeElement(state, id) {
            state.elements = state.elements.filter(el => el.id !== id);
            if (state.selectedElement?.id === id) {
                state.selectedElement = null;
            }
        },

        setSelectedElement(state, element) {
            state.selectedElement = element;
        },

        clearElements(state) {
            state.elements = [];
            state.selectedElement = null;
        },

        setCurrentImage(state, image) {
            state.currentImage = image;
        },

        setEditing(state, editing) {
            state.isEditing = editing;
        },
    },

    actions: {
        async loadPrintAreas({ commit }, productId) {
            try {
                const response = await fetch(`/customization/api/print-areas/${productId}`);
                const data = await response.json();
                commit('setPrintAreas', data.print_areas || []);
            } catch (error) {
                console.error('Failed to load print areas:', error);
                commit('setPrintAreas', []);
            }
        },

        async saveDesign({ state, rootState }) {
            const previewData = {
                elements: state.elements,
                printAreas: state.printAreas,
            };

            return previewData;
        },
    },
};
