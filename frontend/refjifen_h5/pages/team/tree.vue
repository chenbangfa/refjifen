<template>
	<view class="container">
        <view class="node root" v-if="team.root">
            <view class="box">{{ team.root.nickname || team.root.mobile }} ({{ team.root.id }})</view>
            <view class="perf">L: {{ myPerf.left_total || 0 }} | R: {{ myPerf.right_total || 0 }}</view>
        </view>
        
        <view class="branches">
            <view class="branch">
                <view v-if="team.left" class="node">
                     <view class="box">{{ team.left.nickname }} ({{ team.left.id }})</view>
                </view>
                <view v-else class="empty">[Empty Left]</view>
            </view>
            
            <view class="branch">
                <view v-if="team.right" class="node">
                     <view class="box">{{ team.right.nickname }} ({{ team.right.id }})</view>
                </view>
                <view v-else class="empty">[Empty Right]</view>
            </view>
        </view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				team: {},
                myPerf: {}
			}
		},
		onShow() {
            this.loadTeam();
		},
		methods: {
            async loadTeam() {
                const res = await uni.$api('account.php?action=team');
                if (res.code == 200) {
                    this.team = res.data;
                }
                const res2 = await uni.$api('account.php?action=info');
                if(res2.code == 200) {
                    this.myPerf = { left_total: res2.data.left_total, right_total: res2.data.right_total };
                }
            }
		}
	}
</script>

<style>
    .node { text-align: center; margin-bottom: 20px; }
    .box { display: inline-block; padding: 10px; background: #007AFF; color: #fff; border-radius: 5px; }
    .branches { display: flex; justify-content: space-around; }
    .branch { width: 45%; text-align: center; }
    .empty { padding: 10px; border: 1px dashed #ccc; color: #999; }
</style>
