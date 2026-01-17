<template>
    <view class="tree-container">
        <!-- Current Node -->
        <view class="node-wrapper-r">
            <view class="node-card-r" :class="{ 'is-root': isRoot }" @click="handleClick(node)">
                <view class="node-avatar-r">
                    <text class="avatar-txt">用户</text>
                </view>
                <view class="node-content">
                    <view class="nc-name">{{ node.nickname || '用户'+node.id }}</view>
                    <view class="nc-id">ID: {{ node.id }}</view>
                    <view class="nc-perf" style="color:#1890ff">个人: {{ fmt(node.personal_performance) }}</view>
                </view>
            </view>
            <view class="node-stats-r">
                <text class="ns-item">L: {{ fmt(node.left_total) }}</text>
                <text class="ns-item">R: {{ fmt(node.right_total) }}</text>
            </view>
        </view>

        <!-- Children Connectors -->
        <view class="children-wrapper" v-if="hasChildren || showEmpty">
             <!-- Connector Line Vertical -->
            <view class="line-v"></view>
            <!-- Connector Line Horizontal -->
            <view class="line-h" v-if="hasBoth"></view>

            <view class="children-row">
                <!-- Left Child -->
                <view class="child-col">
                    <tree-chart v-if="leftNode" :node="leftNode" @click="$emit('click', $event)"></tree-chart>
                    <view class="empty-node-r" v-else>
                        <text class="empty-plus">+</text>
                        <text class="empty-label">左空</text>
                    </view>
                </view>

                <!-- Right Child -->
                <view class="child-col">
                    <tree-chart v-if="rightNode" :node="rightNode" @click="$emit('click', $event)"></tree-chart>
                    <view class="empty-node-r" v-else>
                        <text class="empty-plus">+</text>
                        <text class="empty-label">右空</text>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script>
    export default {
        name: 'tree-chart',
        props: {
            node: { type: Object, default: () => ({}) },
            isRoot: { type: Boolean, default: false }
        },
        computed: {
            leftNode() {
                return (this.node.children || []).find(c => c.position == 'L');
            },
            rightNode() {
                return (this.node.children || []).find(c => c.position == 'R');
            },
            hasChildren() {
                return this.leftNode || this.rightNode;
            },
            hasBoth() {
                 return true; // Always show horizontal line logic for balanced tree look? Or only if 2 children? 
                 // Actually, if we want to show Empty slots, we ALWAYS render 2 slots (L and R).
                 // So we always have 2 branches.
                 return true; 
            },
            showEmpty() {
                return true; // Always show structure
            }
        },
        methods: {
            fmt(val) {
                return parseFloat(val||0).toFixed(2);
            },
            handleClick(n) {
                this.$emit('click', n);
            }
        }
    }
</script>

<style>
    .tree-container { display: flex; flex-direction: column; align-items: center; }
    
    .node-wrapper-r { padding: 10px; display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2; }
    .node-card-r { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 8px; display: flex; align-items: center; min-width: 120px; border: 1px solid #eee; transition: .2s; }
    .node-card-r:active { transform: scale(0.95); background: #e6f7ff; }
    .node-card-r.is-root { border: 2px solid #1890ff; padding: 12px; min-width: 150px; }
    
    .node-avatar-r { width: 30px; height: 30px; border-radius: 15px; background: #1890ff; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 10px; margin-right: 8px; flex-shrink: 0; }
    .node-content { font-size: 12px; }
    .nc-name { font-weight: bold; color: #333; }
    .nc-id { color: #999; transform: scale(0.9); transform-origin: left; }
    .nc-perf { transform: scale(0.9); transform-origin: left; margin-top: 2px; }
    
    .node-stats-r { font-size: 10px; color: #666; background: #fafafa; padding: 2px 6px; border-radius: 10px; margin-top: 4px; border: 1px solid #eee; }
    .ns-item { margin: 0 4px; }
    
    .children-wrapper { display: flex; flex-direction: column; align-items: center; position: relative; width: 100%; }
    .line-v { width: 1px; height: 15px; background: #ccc; }
    .line-h { width: 50%; height: 1px; background: #ccc; position: absolute; top: 15px; left: 25%; }
    
    .children-row { display: flex; justify-content: center; width: 100%; padding-top: 15px; }
    .child-col { display: flex; flex-direction: column; align-items: center; padding: 0 5px; width: 50%; box-sizing: border-box; position: relative; }
    .child-col::before { content: ''; position: absolute; top: -15px; left: 50%; width: 1px; height: 15px; background: #ccc; }
    
    .empty-node-r { width: 40px; height: 40px; border: 1px dashed #ddd; border-radius: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(255,255,255,0.5); margin-top: 10px; }
    .empty-plus { color: #ccc; font-size: 16px; line-height: 1; }
    .empty-label { color: #ccc; font-size: 9px; transform: scale(0.8); }
</style>
