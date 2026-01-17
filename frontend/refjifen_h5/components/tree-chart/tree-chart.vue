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

        <!-- Case 1: Children Loaded -> Show Children (and empty slots if any side is missing) -->
        <view class="children-wrapper" v-if="childrenLoaded">
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

        <!-- Case 2: Children NOT Loaded but Exist -> Show Drill Down Hint -->
        <view class="expand-wrapper" v-else-if="hasRealChildren" @click="handleClick(node)">
            <view class="line-v-short"></view>
            <view class="expand-btn">
                <text class="expand-icon">↓</text>
                <text class="expand-txt">下级</text>
            </view>
        </view>

        <!-- Case 3: No Children & Not Loaded -> Show Empty Slots -->
        <view class="children-wrapper" v-else>
             <view class="line-v"></view>
             <view class="line-h"></view>
             <view class="children-row">
                <view class="child-col">
                    <view class="empty-node-r">
                        <text class="empty-plus">+</text>
                        <text class="empty-label">左空</text>
                    </view>
                </view>
                <view class="child-col">
                    <view class="empty-node-r">
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
            // Check if children array is populated
            childrenLoaded() {
                return this.node.children && this.node.children.length > 0;
            },
            // Check if backend says "I have children" (count > 0)
            hasRealChildren() {
                return (this.node.child_count || 0) > 0;
            },
            leftNode() {
                return (this.node.children || []).find(c => c.position == 'L');
            },
            rightNode() {
                return (this.node.children || []).find(c => c.position == 'R');
            },
            hasBoth() {
                 return true; 
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

    .expand-wrapper { display: flex; flex-direction: column; align-items: center; cursor: pointer; }
    .line-v-short { width: 1px; height: 10px; background: #ccc; }
    .expand-btn { background: #f0faff; border: 1px solid #1890ff; padding: 2px 8px; border-radius: 10px; display: flex; align-items: center; margin-top: 0; }
    .expand-icon { font-size: 12px; color: #1890ff; margin-right: 2px; }
    .expand-txt { font-size: 10px; color: #1890ff; }
</style>
