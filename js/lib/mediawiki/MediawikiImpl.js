"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
const awaitReady_1 = require("../decorators/awaitReady");
const AbstractMediawiki_1 = require("./AbstractMediawiki");
const cachable_1 = require("../decorators/cachable");
const Mediawiki_1 = require("./Mediawiki");
class MediawikiImpl extends AbstractMediawiki_1.AbstractMediawiki {
    async editAppend(title, summary, text) {
        await this.post({
            action: "edit",
            appendtext: text,
            title: title,
            summary: summary,
            starttimestamp: this.getNowString(),
            //watchlist: "nochange",
            token: await this.fetchToken("csrf")
        }, true);
    }
    async categoryMembersRecurse(category, depth) {
        if (depth > 0) {
            var members = [];
            const currentMembers = await this.categoryMembers(category);
            await Promise.all(currentMembers.map(async (prefixedMember) => {
                if (prefixedMember.startsWith(Mediawiki_1.CATEGORY_PREFIX)) {
                    const member = prefixedMember.substring(Mediawiki_1.CATEGORY_PREFIX.length);
                    const newMembers = await this.categoryMembersRecurse(member, depth - 1);
                    newMembers.forEach(newMember => newMember.unshift(prefixedMember));
                    members.push(...newMembers);
                }
                else {
                    members.push([prefixedMember]);
                }
            }));
            return members.sort(([a], [b]) => a.localeCompare(b));
        }
        else {
            return [[]];
        }
    }
    async categoryMembers(category) {
        const values = await this.query({
            generator: "categorymembers",
            gcmtitle: `${Mediawiki_1.CATEGORY_PREFIX}${category}`,
            prop: "info",
            gcmlimit: "max"
        }, ["query", "pages"]);
        return values ? Object.values(values).map(o => o.title) : [];
    }
}
__decorate([
    awaitReady_1.awaitReady()
], MediawikiImpl.prototype, "editAppend", null);
__decorate([
    cachable_1.cachable(),
    awaitReady_1.awaitReady()
], MediawikiImpl.prototype, "categoryMembersRecurse", null);
__decorate([
    cachable_1.cachable(),
    awaitReady_1.awaitReady()
], MediawikiImpl.prototype, "categoryMembers", null);
exports.MediawikiImpl = MediawikiImpl;
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiTWVkaWF3aWtpSW1wbC5qcyIsInNvdXJjZVJvb3QiOiIiLCJzb3VyY2VzIjpbIk1lZGlhd2lraUltcGwudHMiXSwibmFtZXMiOltdLCJtYXBwaW5ncyI6Ijs7Ozs7Ozs7QUFBQSx5REFBc0Q7QUFDdEQsMkRBQXdEO0FBQ3hELHFEQUFrRDtBQUNsRCwyQ0FBeUQ7QUFFekQsTUFBYSxhQUFjLFNBQVEscUNBQWlCO0lBR2hELEtBQUssQ0FBQyxVQUFVLENBQUMsS0FBYSxFQUFFLE9BQWUsRUFBRSxJQUFZO1FBQ3pELE1BQU0sSUFBSSxDQUFDLElBQUksQ0FBQztZQUNaLE1BQU0sRUFBRSxNQUFNO1lBQ2QsVUFBVSxFQUFFLElBQUk7WUFDaEIsS0FBSyxFQUFFLEtBQUs7WUFDWixPQUFPLEVBQUUsT0FBTztZQUNoQixjQUFjLEVBQUUsSUFBSSxDQUFDLFlBQVksRUFBRTtZQUNuQyx3QkFBd0I7WUFDeEIsS0FBSyxFQUFFLE1BQU0sSUFBSSxDQUFDLFVBQVUsQ0FBQyxNQUFNLENBQUM7U0FDdkMsRUFBRSxJQUFJLENBQUMsQ0FBQztJQUNiLENBQUM7SUFJRCxLQUFLLENBQUMsc0JBQXNCLENBQUMsUUFBZ0IsRUFBRSxLQUFhO1FBQ3hELElBQUksS0FBSyxHQUFHLENBQUMsRUFBRTtZQUNYLElBQUksT0FBTyxHQUFlLEVBQUUsQ0FBQztZQUM3QixNQUFNLGNBQWMsR0FBRyxNQUFNLElBQUksQ0FBQyxlQUFlLENBQUMsUUFBUSxDQUFDLENBQUM7WUFDNUQsTUFBTSxPQUFPLENBQUMsR0FBRyxDQUFDLGNBQWMsQ0FBQyxHQUFHLENBQUMsS0FBSyxFQUFDLGNBQWMsRUFBQyxFQUFFO2dCQUN4RCxJQUFJLGNBQWMsQ0FBQyxVQUFVLENBQUMsMkJBQWUsQ0FBQyxFQUFFO29CQUM1QyxNQUFNLE1BQU0sR0FBRyxjQUFjLENBQUMsU0FBUyxDQUFDLDJCQUFlLENBQUMsTUFBTSxDQUFDLENBQUM7b0JBQ2hFLE1BQU0sVUFBVSxHQUFHLE1BQU0sSUFBSSxDQUFDLHNCQUFzQixDQUFDLE1BQU0sRUFBRSxLQUFLLEdBQUcsQ0FBQyxDQUFDLENBQUM7b0JBQ3hFLFVBQVUsQ0FBQyxPQUFPLENBQUMsU0FBUyxDQUFDLEVBQUUsQ0FBQyxTQUFTLENBQUMsT0FBTyxDQUFDLGNBQWMsQ0FBQyxDQUFDLENBQUM7b0JBQ25FLE9BQU8sQ0FBQyxJQUFJLENBQUMsR0FBRyxVQUFVLENBQUMsQ0FBQztpQkFDL0I7cUJBQU07b0JBQ0gsT0FBTyxDQUFDLElBQUksQ0FBQyxDQUFDLGNBQWMsQ0FBQyxDQUFDLENBQUM7aUJBQ2xDO1lBQ0wsQ0FBQyxDQUFDLENBQUMsQ0FBQztZQUNKLE9BQU8sT0FBTyxDQUFDLElBQUksQ0FBQyxDQUFDLENBQUMsQ0FBQyxDQUFDLEVBQUUsQ0FBQyxDQUFDLENBQUMsRUFBRSxFQUFFLENBQUMsQ0FBQyxDQUFDLGFBQWEsQ0FBQyxDQUFDLENBQUMsQ0FBQyxDQUFDO1NBQ3pEO2FBQU07WUFDSixPQUFPLENBQUMsRUFBRSxDQUFDLENBQUM7U0FDZDtJQUNMLENBQUM7SUFJRCxLQUFLLENBQUMsZUFBZSxDQUFDLFFBQWdCO1FBQ2xDLE1BQU0sTUFBTSxHQUFHLE1BQU0sSUFBSSxDQUFDLEtBQUssQ0FBQztZQUM1QixTQUFTLEVBQUUsaUJBQWlCO1lBQzVCLFFBQVEsRUFBRSxHQUFHLDJCQUFlLEdBQUcsUUFBUSxFQUFFO1lBQ3pDLElBQUksRUFBRSxNQUFNO1lBQ1osUUFBUSxFQUFFLEtBQUs7U0FDbEIsRUFBRSxDQUFDLE9BQU8sRUFBRSxPQUFPLENBQUMsQ0FBQyxDQUFDO1FBRXZCLE9BQU8sTUFBTSxDQUFDLENBQUMsQ0FBQyxNQUFNLENBQUMsTUFBTSxDQUFDLE1BQU0sQ0FBQyxDQUFDLEdBQUcsQ0FBQyxDQUFDLENBQUMsRUFBRSxDQUFPLENBQUUsQ0FBQyxLQUFLLENBQUMsQ0FBQyxDQUFDLENBQUMsRUFBRSxDQUFDO0lBQ3hFLENBQUM7Q0FDSjtBQTlDRztJQURDLHVCQUFVLEVBQUU7K0NBV1o7QUFJRDtJQUZDLG1CQUFRLEVBQUU7SUFDVix1QkFBVSxFQUFFOzJEQW1CWjtBQUlEO0lBRkMsbUJBQVEsRUFBRTtJQUNWLHVCQUFVLEVBQUU7b0RBVVo7QUFoREwsc0NBaURDIn0=